<?php
namespace xepan\marketing;

class SocialPosters_Facebook_FacebookConfig extends \xepan\marketing\Model_SocialPosters_Base_SocialConfig {
	
	public $status = ['Active','Inactive'];
	public $actions = [
			'Active'=>['view','edit','delete','login_url','users','fetch_social_page','deactivate'],
			'Inactive'=>['view','edit','delete','active']
		];

	function init(){

		parent::init();
		// $this->getElement('social_app')->defaultValue('Facebook');
		$this->addCondition('social_app','Facebook');

		// $this->getElement('type')->defaultValue('FacebookConfig');
		$this->addCondition('type','SocialPosters_Facebook_FacebookConfig');
	}

	function page_login_url($page){
		$config_model = $this->add('xepan/marketing/SocialPosters_Facebook_FacebookConfig');
		$config_model->load($this->id);
		$config = array(
	      'appId' => $config_model['appId'],
	      'secret' => $config_model['secret'],
	      'fileUpload' => true, // optional
	      'allowSignedRequest' => false, // optional, but should be set to false for non-canvas apps
		);
		// $facebook = new \Facebook($config);
		$page->add('View')->setElement('a')->setAttr('href','index.php?page=xepan_marketing_socialloginmanager&social_login_to=Facebook&for_config_id='.$config_model->id)->setAttr('target','_blank')->set("Login Url");

	}

	function page_users($page){
		$config_id = $this->id;

		$user_crud = $page->add("xepan\base\CRUD",['frame_options'=>['width'=>'600'],'entity_name'=>"Facebook User"]);
		$user_model = $page->add('xepan/marketing/Model_SocialPosters_Base_SocialUsers');
		$user_model->addCondition('config_id',$config_id);
		$user_crud->setModel($user_model,['name','userid','userid_returned','access_token','access_token_secret','access_token_expiry','is_access_token_valid','post_on_pages','post_on_timeline'],['name','userid','userid_returned','is_access_token_valid','post_on_pages','post_on_timeline']);

		$user_crud->grid->add('VirtualPage')
			->addColumn('facebook_page')
			->set(function($page){

				$id = $_GET[$page->short_name.'_id'];
				$user_model = $page->add('xepan/marketing/Model_SocialPosters_Base_SocialUsers')->load($id);
				
				$saved_data_array = json_decode($user_model['extra'],true);
				$saved_post_page_for_form = [];
				$saved_page_array_for_grid = [];
				$saved_pages = $saved_data_array['data'];
				foreach ($saved_pages as $key => $fb_page) {
					$new_key = 	$fb_page['name']."-".$fb_page['id'];
					$saved_page_array_for_grid[$new_key] = ['fb_page_id'=>$fb_page['id'],"name"=>$fb_page['name'],"access_token"=>$fb_page['access_token'],'send_post'=>$fb_page['send_post']];
					if($fb_page['send_post'])
						$saved_post_page_for_form[] = $new_key;

				}

				$page_crud = $page->add('xepan\base\Grid');
				$form = $page->add('Form');
				$send_post_on_page = $form->addField('hidden','send_post_on_page')->set(json_encode($saved_post_page_for_form));
				$form->addSubmit('Update');

				$model = $this->add('Model');
				$model->setSource("Array",$saved_page_array_for_grid);
				$page_crud->setModel($model);
				$page_crud->addSelectable($send_post_on_page);

				if($form->isSubmitted()){
					$selected_pages_array = json_decode($form['send_post_on_page']);

					foreach ($saved_pages as $key => $fb_page) {
						$send_post = 0;
						if(in_array($fb_page['name']."-".$fb_page['id'], $selected_pages_array)){
							$send_post = 1;
						}
						$saved_pages[$key]['send_post'] = $send_post;
					}

					$user_model['extra'] = json_encode(['data'=>$saved_pages]);
					$user_model->save();
					$form->js(null,$form->js()->reload())->univ()->successMessage("successfully saved")->execute();
				}
			});
	}

	function page_fetch_social_page($page){

		$user_model = $page->add('xepan/marketing/Model_SocialPosters_Base_SocialUsers');
		$user_model->addCondition('config_id',$this->id);

		$form = $page->add('Form');
		$user_field = $form->addField('DropDown','user')->validate('required');
		$user_field->setModel($user_model);
		$form->addSubmit("Get Page");

		if($form->isSubmitted()){
			$config_model = $this->add('xepan/marketing/SocialPosters_Facebook_FacebookConfig');
			$config_model->load($this->id);
			$config = array(
		      'appId' => $config_model['appId'],
		      'secret' => $config_model['secret'],
		      'fileUpload' => true, // optional
		      'allowSignedRequest' => false, // optional, but should be set to false for non-canvas apps
			);
	
			$user_model = $page->add('xepan/marketing/Model_SocialPosters_Base_SocialUsers')->load($form['user']);
			$facebook = $this->add('xepan\marketing\SocialPosters_Facebook');
			try{
				$pages = $facebook->getPage($user_model);	
			}catch(\Exception $e){
				throw $e;
			}

			// managing previous saved page with send_post or not
			//1.	get saved pages array of id with send_post and other info
			$saved_page = json_decode($user_model['extra'],true);
			$saved_page_array = [];
			$saved_pages = isset($saved_page['data'])?$saved_page['data']:[];

			foreach ($saved_pages as $key => $page) {
				$saved_page_array[$page['id']] = ["name"=>$page['name'],"access_token"=>$page['access_token'],'send_post'=>$page['send_post']];
			}

			//2.	adding send_post option to new page array
			$new_page_array = json_decode($pages,true);
			$new_pages = isset($new_page_array['data'])?$new_page_array['data']:[];

			foreach ($new_pages as $key => $new_page) {
				$send_post = true;
				if(isset($saved_page_array[$new_page['id']])){
					$send_post = $saved_page_array[$new_page['id']]['send_post'];
					unset($saved_page_array[$new_page['id']]);
				}

				$new_page_array['data'][$key]['send_post'] = $send_post;
			}

			$user_model['extra'] = json_encode($new_page_array);
			$user_model->save();

			$form->js(null,$form->js()->reload())->univ()->successMessage("Page factched successfully")->execute();
		}

	}

	function getFBPageArray($json){	
		$data = [];
		$data_array = json_decode($json,true);
		$saved_pages = isset($data_array['data'])?$data_array['data']:[];

		foreach ($saved_pages as $key => $page) {
			$saved_page_array[$page['id']] = $page;
		}

		return $data;
	}

	//Config Deactivated
	function deactivate(){
		$this['status']='InActive';
		$this->app->employee
            ->addActivity("Facebook Configuration : '". $this['name'] ."' has been deactivated", null /*Related Document ID*/, null /*Related Contact ID*/,null,null,"xepan_marketing_socialconfiguration")
            ->notifyWhoCan('activate','InActive',$this);
		return $this->save();
	}

	//Activate Facebook Config
	function activate(){
		$this['status']='Active';
		$this->app->employee
            ->addActivity("Facebook Configuration : '". $this['name'] ."' now active", null /*Related Document ID*/, null /*Related Contact ID*/,null,null,"xepan_marketing_socialconfiguration")
            ->notifyWhoCan('deactivate','Active',$this);
		$this->save();
	}
}