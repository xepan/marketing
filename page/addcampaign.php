<?php
namespace xepan\marketing;
class page_addcampaign extends \xepan\base\Page{
	public $title="Add Campaign";
		public $breadcrumb=['Home'=>'index','Campaign'=>'xepan_marketing_campaign','AddCampaign'=>'#'];

	function init(){
		parent::init();	

		$action = $this->api->stickyGET('action')?:'view';
		$campaign = $this->add('xepan\marketing\Model_Campaign')->tryLoadBy('id',$this->api->stickyGET('document_id'));

		$camp = $this->add('xepan\hr\View_Document',['action'=>$action,'id_field_on_reload'=>'document_id'],null,['view/addcampaign']);
	    $camp->setModel($campaign,['title', 'starting_date', 'ending_date', 'campaign_type'],['title', 'starting_date', 'ending_date', 'campaign_type']);
		
		$campaign_m = $this->add('xepan\marketing\Model_Campaign');
		$associated_categories = [];
		$associated_users = [];

		if($document_id = $_GET['document_id']){
			$campaign_m->load($document_id);
			$associated_categories = $campaign_m->getAssociatedCategories();
			$associated_users = $campaign_m->getAssociatedUsers();
		}

		$form = $camp->form;
		
		$category_field = $form->addField('xepan\base\DropDown','category');
		$category_field->setModel('xepan\marketing\Model_MarketingCategory');
		$category_field->set($associated_categories)->js(true)->trigger('changed');
		$category_field->setAttr(['multiple'=>'multiple']);
		
		$user_field = $form->addField('xepan\base\DropDown','user');
		$user_field->setModel('xepan\marketing\Model_SocialPosters_Base_SocialUsers');
		$user_field->set($associated_users)->js(true)->trigger('changed');
		$user_field->setAttr(['multiple'=>'multiple']);

	    $camp->form->onSubmit(function($f)use($campaign_m){
    		$f->save();

    		if($campaign_m->loaded()){
				$campaign_m->removeAssociateCategory();
				$campaign_m->removeAssociateUser();
    		}

    		if($f['category']){
				$categories = explode(",",$f['category']);
				foreach ($categories as $key => $cat_id) {
					if(!is_numeric($cat_id))
						continue;

					$this->add('xepan\marketing\Model_Campaign_Category_Association')
						->addCondition('campaign_id',$f->model->id)
		     			->addCondition('marketing_category_id',$cat_id)
		     			->addCondition('created_at',$this->app->now)
			 			->tryLoadAny()	
			 			->save();
				}
			}

			if($f['user']){
				$user = explode(",",$f['user']);
				foreach ($user as $key => $user_id) {
					if(!is_numeric($user_id))
						continue;

					$this->add('xepan\marketing\Model_Campaign_SocialUser_Association')
						->addCondition('campaign_id',$f->model->id)
		     			->addCondition('socialuser_id',$user_id)
			 			->tryLoadAny()	
			 			->save();
				}
			}

    		if($f->model['campaign_type']=='campaign'){
    			return $this->js()->univ()->redirect($this->app->url('xepan_marketing_schedule',['campaign_id'=>$f->model->id]));	
			}else{
    			return $this->js()->univ()->redirect($this->app->url('xepan_marketing_subscriberschedule',['campaign_id'=>$f->model->id]));	
			}
    	});
	}
}