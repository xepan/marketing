<?php

namespace xepan\marketing;

// EXTRA  MODELS DEFINED AT THE BOTTOM OF THIS FILES

class SocialPosters_Linkedin extends SocialPosters_Base_Social{
	public $client=null;
	public $client_config=null;

	function init(){
		parent::init();
	}

	function setup_client($client_config_id){
		$this->client_config = $client_config = $this->add('xepan/marketing/SocialPosters_Linkedin_LinkedinConfig')->load($client_config_id);
		
		$this->client = $client = new \oauth_client_class;
		$client->debug = 1;
		$client->debug_http = 1;
		$client->server = 'LinkedIn';
		$client->redirect_uri = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?page=xepan_marketing_socialafterloginhandler&xfrom=Linkedin&client_config_id='.$client_config_id;

		$client->client_id = $this->client_config['appId']; $application_line = __LINE__;
		$client->client_secret = $this->client_config['secret'];
		// $client->access_token = $this->client_config['access_token'];

		/*  API permission scopes
		 *  Separate scopes with a space, not with +
		 */
		// $client->scope = 'rw_company_admin w_messages r_basicprofile r_contactinfo r_fullprofile r_network r_emailaddress rw_nus rw_groups';
		$client->scope = "w_share";
		$client->Initialize();
	}

	function login_status(){
		// $this->setup_client($_GET['for_config_id']);
		$config_model = $this->add('xepan/marketing/SocialPosters_Linkedin_LinkedinConfig')->load($_GET['for_config_id']);
		$redirect_url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?page=xepan_marketing_socialafterloginhandler&xfrom=Linkedin&client_config_id='.$config_model->id;
		$li = new \LinkedIn\LinkedIn(
		  array(
		    'api_key' => $config_model['appId'], 
		    'api_secret' => $config_model['secret'], 
		    'callback_url' => $redirect_url
		  )
		);

		$login_url = $li->getLoginUrl(
		  array(
		    \LinkedIn\LinkedIn::SCOPE_BASIC_PROFILE, 
		    \LinkedIn\LinkedIn::SCOPE_EMAIL_ADDRESS
		  )
		);
		
		return '<a href="' . $login_url . '">Log in with Linkedin!</a>';
		
	}

	function after_login_handler(){
		$config_model = $this->add('xepan/marketing/SocialPosters_Linkedin_LinkedinConfig')->load($_GET['client_config_id']);

		if(!$config_model->loaded()){
			$this->add('View_Error')->set('Could not load Config Model');
			return false;
		}		

		$redirect_url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?page=xepan_marketing_socialafterloginhandler&xfrom=Linkedin&client_config_id='.$config_model->id;
		$li = new \LinkedIn\LinkedIn(
		  array(
		    'api_key' => $config_model['appId'], 
		    'api_secret' => $config_model['secret'], 
		    'callback_url' => $redirect_url
		  )
		);
		
		if(!$li){
			return "Configuration Problem";
		}
		
		if(!$_REQUEST['code']){
			echo "linkedin code not found";
			return false;
		}

		$token = $li->getAccessToken($_REQUEST['code']);
		$token_expires = $li->getAccessTokenExpiration();
		
		$info = $li->get('/people/~');

		$linkedin_user = $this->add('xepan/marketing/Model_SocialPosters_Base_SocialUsers');
		$linkedin_user->addCondition('userid_returned',$info['id']);
		$linkedin_user->addCondition('config_id',$config_model->id);
		$linkedin_user->tryLoadAny();

		$linkedin_user['name'] = $info['firstName'];
		$linkedin_user['access_token'] = $token;
		// $li_user['access_token_secret'] = $this->client->access_token_secret;
		$linkedin_user['access_token_expiry'] = $token_expires;
		$linkedin_user->save();		
		return true;
	}


	function config_page(){
		$config_model = $this->add('xepan/marketing/SocialPosters_Linkedin_LinkedinConfig');

		$c = $this->owner->add('CRUD',array('allow_add'=>true,'allow_del'=>true));
		$c->setModel($config_model);
		
		$users_crud = $c->addRef('xepan/marketing/Model_SocialPosters_SocialUsers',array('label'=>'Users'));

		if($c->grid and !$users_crud){
			$f=$c->addFrame('Login URL');
			if($f){
				$config_model = $this->add('xepan/marketing/SocialPosters_Linkedin_LinkedinConfig');
				$config_model->load($c->id);

				$f->add('View')->setElement('a')->setAttr('href','index.php?page=xepan_marketing_socialloginmanager&social_login_to=Linkedin&for_config_id='.$config_model->id)->setAttr('target','_blank')->set('index.php?page=xepan_marketing_socialloginmanager&social_login_to=Linkedin&for_config_id='.$config_model->id);
			}
		}
	}

	function postSingle($user_model,$params,$post_in_groups=true, $groups_posted=array(),$under_campaign_id=0){
		if(! $user_model instanceof xepan\marketing\Model_SocialPosters_Base_SocialUsers AND !$user_model->loaded()){
			throw $this->exception('User must be a loaded model of Social User Type','Growl');
		}

		$config_model = $user_model->ref('config_id');

		$this->setup_client($config_model->id);

  		$client = $this->client;
  		$client->access_token = $user_model['access_token'];
		$client->access_token_secret = $user_model['access_token_secret'];

		// echo $client->access_token;
		// exit;

  		$parameters = new \stdClass;

  		$activity_type = 'shares';
		// Its a share 

		/*
			<?xml version="1.0" encoding="UTF-8"?>
			<share>
			    <comment>83% of employers will use social media to hire: 78% LinkedIn, 55% Facebook, 45% Twitter [SF Biz Times] http://bit.ly/cCpeOD</comment>
			    <content>
			        <title>Survey: Social networks top hiring tool - San Francisco Business Times</title>
			        <submitted-url>http://sanfrancisco.bizjournals.com/sanfrancisco/stories/2010/06/28/daily34.html</submitted-url>
			        <submitted-image-url>http://images.bizjournals.com/travel/cityscapes/thumbs/sm_sanfrancisco.jpg</submitted-image-url>
			    </content>
			    <visibility>
			        <code>anyone</code>
			    </visibility>
			</share>
		*/

		$parameters = new \stdClass;
		$parameters->visibility = new \stdClass;
		$parameters->visibility->code = 'anyone';
  		if($params['message_255']) $parameters->comment = $params['message_255'];
  		
		if($params['url']){
			$parameters->content = new \stdClass;
	  		$parameters->content->{'submitted-url'} = $params['url'];
	  		if($params['post_title']) 
	  			$parameters->content->title = $params['post_title'];

	  		if($params['first_image']){
	  			$parameters->content->{'submitted-image-url'} = 'http://'.$_SERVER['HTTP_HOST'].'/' .$params['first_image'];
	  		}
		}
		// throw new \Exception("outside", 1);		
		$success = $client->CallAPI('http://api.linkedin.com/v1/people/~/'.$activity_type.'?format=json','POST', $parameters, array('FailOnAccessError'=>true, 'RequestContentType'=>'application/json'), $new_post);
		$success = $client->Finalize($success);
		$social_posting_save = $this->add('xepan/marketing/Model_SocialPosters_Base_SocialPosting');
		$social_posting_save->create($user_model->id, $params->id, $new_post->updateKey, $activity_type, $new_post->updateUrl,"", $under_campaign_id);
		

		// Post in all groups

		if($post_in_groups){
			$success = $client->CallAPI(
						'http://api.linkedin.com/v1/people/~/group-memberships',
						'GET', null, array('FailOnAccessError'=>true, 'RequestContentType'=>'application/json'), $groups);

			$groups =simplexml_load_string($groups);
			$groups = json_encode($groups);
			$groups = json_decode($groups,true);
			// echo "<pre>";
			// print_r($groups['group-membership']);
			$parameters->title = $parameters->content->title;
			$parameters->summary = $parameters->comment;

			unset($parameters->visibility);
			unset($parameters->comment);

			if(isset($groups['group-membership'])){
				foreach ($groups['group-membership'] as $grp) {
					// print_r($grp);
					$grp_id= $grp['group']['id'];
					// echo $grp_id ."<br/>";
					if(!in_array($grp_id, $groups_posted) OR !$this->client_config['filter_repeated_posts']){
						try{

							$success = $client->CallAPI(
								'http://api.linkedin.com/v1/groups/'.$grp_id.'/posts?format=json',
								'POST', $parameters, array('FailOnAccessError'=>true, 'RequestContentType'=>'application/json'), $group_post, $headers);
							// echo $headers['location'];
							$success = $client->Finalize($success);
							$groups_posted[] = $grp_id;

							// Get Grup post URL 
							$group_post_id = explode("/",$headers['location']);
							$group_post_id = $group_post_id[count($group_post_id)-1];
							$success = $client->CallAPI(
								'http://api.linkedin.com/v1/posts/'.$group_post_id.':(site-group-post-url)?format=json',
								'GET', $parameters, array('FailOnAccessError'=>true, 'RequestContentType'=>'application/json'), $group_post_url, $headers_2);

							$social_posting_save->create($user_model->id, $params->id, $group_post_url->siteGroupPostUrl, 'Group Post', $headers['location'], $grp['group']['name'], $under_campaign_id);
						}catch(\Exception $e){
							print_r($headers);
							throw $e;
							continue;
						}
					}
				}
			}

		}

	}

	function postAll($params,$under_campaign_id=0){ // all social post row as hash array or model

  		$groups_posted=array();

  		$config_model = $this->add('xepan/marketing/SocialPosters_Linkedin_LinkedinConfig');
  		foreach ($config_model as $junk) {	  			
	  		$users=$config_model->ref('xepan/marketing/SocialPosters_Base_SocialUsers');
	  		$users->addCondition('is_active',true);

	  		foreach ($users as $junk) {
	  			$this->postSingle($users,$params,$config_model['post_in_groups'], $groups_posted, $under_campaign_id);
	  		}
	  	}	  	
	}

	function icon($only_css_class=false){
		if($only_css_class) 
			return "fa fa-linkedin";
		return "<i class='fa fa-linkedin'></i>";
	}



	function profileURL($user_id_pk,$other_user_id=false){

		if(!$other_user_id){
			$user = $this->add('xepan/marketing/Model_SocialPosters_Base_SocialUsers')->tryLoad($user_id_pk);
			if(!$user->loaded()) return false;
			$other_user_id = $user['userid_returned'];
			$name=$user['name'];
		}else{
			$id_name_array=explode("_", $other_user_id);
			$other_user_id=$id_name_array[0];
			$name=$id_name_array[1];
		}

		return array('url'=>'javascript:void(0)','name'=>$name);

		return array('url'=>"https://www.linkedin.com/profile/view?id=" . $other_user_id ."/",'name'=>$name);

		return "https://www.linkedin.com/profile/view?id=".$user['userid_returned'];
		// return "https://www.facebook.com/profile.php?id=".$user['userid'];
	}

	function postURL($post_id_returned){

		$post = $this->add('xepan/marketing//Model_SocialPosters_Base_SocialPosting')->tryLoadBy('postid_returned',$post_id_returned);
		if(!$post->loaded()) return false;
				
		$post_id_returned_array = explode("-", $post_id_returned);
		$topic_id = $post_id_returned_array[count($post_id_returned_array)-1];
		if(count($post_id_returned_array) ==3){
			// UPDATE-384280894-5949163916801175552 Its a share
			return $post['group_id'];
			// returned url of post is saved here so just returning thiis field
			// return "https://www.linkedin.com/nhome/updates?topic=".$topic_id;
		}

		return $post_id_returned;

	}

	function groupURL($group_id){
		throw $this->exception('Define in extnding class');
	}

	function updateActivities($posting_model){
		if(! $posting_model instanceof xepan/marketing\Model_SocialPosting and !$posting_model->loaded())
			throw $this->exception('Posting Model must be a loaded instance of Model_SocialPosting','Growl');

		
		$user_model = $posting_model->ref('user_id');
		$config_model = $user_model->ref('config_id');

		$this->setup_client($config_model->id);

  		$client = $this->client;
  		$client->access_token = $user_model['access_token'];
		$client->access_token_secret = $user_model['access_token_secret'];


		$parameters=array();

  		// likes
  		$likes_count = 0;

  		$post_id_returned_array = explode("-", $posting_model['postid_returned']);
		$topic_id = $post_id_returned_array[count($post_id_returned_array)-1];
		// echo "testing ". $posting_model['postid_returned'] .' '. count($post_id_returned_array).'<br/>';
		if(count($post_id_returned_array) ==3){
	  		// For network-updates
			// UPDATE-384280894-5949163916801175552 Its a share/ network-update
			$success = $client->CallAPI(
					'http://api.linkedin.com/v1/people/~/network/updates/key='.$posting_model['postid_returned'].':(likes,update-comments)?format=json',
					'GET', $parameters, array('FailOnAccessError'=>true, 'RequestContentType'=>'application/json'), $likes_comments, $headers);
			$likes_comments = json_encode($likes_comments);
			$likes_comments = json_decode($likes_comments,true);
			// echo"<pre>";
			// print_r($likes_comments);
			// echo"</pre>";

			$likes_count=$likes_comments['likes']['_total'];
			$posting_model->updateLikesCount($likes_count);
			if($likes_comments['updateComments']['_total']){
				foreach ($likes_comments['updateComments']['values'] as $comment) {
					$activity = $this->add('xepan/marketing/Model_Activity');
					$activity->addCondition('posting_id',$posting_model->id);
					$activity->addCondition('activityid_returned',$comment['id']);
					$activity->tryLoadAny();

					$activity['activity_type']='Comment';
					$activity['activity_on']=date('Y-m-d H:i:s',$comment['timestamp']);
					$activity['activity_by']=$comment['person']['id'].'_'.$comment['person']['firstName'] .' '. $comment['person']['lastName'];
					$activity['name']=$comment['comment'];
					$activity['action_allowed']="";
					$activity->save();
				}
			}

		}else{
	  		// For group posts
	  		$post_id_array = explode("/",$posting_model['group_id']);
	  		$post_id= $post_id_array[count($post_id_array)-1];

			$success = $client->CallAPI(
								'http://api.linkedin.com/v1/posts/'.$post_id.':(id,type,category,creator,title,summary,creation-timestamp,relation-to-viewer:(is-following,is-liked,available-actions),likes,comments,attachment,site-group-post-url)?format=json',
								'GET', $parameters, array('FailOnAccessError'=>true, 'RequestContentType'=>'application/json'), $group_post_details, $headers_2);
			$group_post_details = json_encode($group_post_details);
			$group_post_details = json_decode($group_post_details,true);
			// echo"<pre>";
			// print_r($group_post_details);
			// echo"</pre>";
			// exit;
			// echo "Found likes ".$group_post_details['likes']['_total'];
			// save likes count
			if(isset($group_post_details['likes']))
				$posting_model->updateLikesCount($group_post_details['likes']['_total']);

			// echo "passed";
			// save shares count
			// save all comments
			if(isset($group_post_details['comments']) and $group_post_details['comments']['_total']){
				foreach ($group_post_details['comments']['values'] as $comment) {
					$activity = $this->add('xepan/marketing/Model_Activity');
					$activity->addCondition('posting_id',$posting_model->id);
					$activity->addCondition('activityid_returned',$comment['id']);
					$activity->tryLoadAny();

					$activity['activity_type']='Comment';
					$activity['activity_on']=date('Y-m-d H:i:s',$comment['creationTimestamp']);
					$activity['activity_by']=$comment['creator']['id'].'_'.$comment['creator']['firstName'] .' '. $comment['creator']['lastName'];
					$activity['name']=$comment['text'];
					if($comment['relationToViewer']['availableActions']['_total']){
						$action_allowed = array();
						foreach ($comment['relationToViewer']['availableActions']['values'] as $acts) {
							$action_allowed[] = $acts['code'];
						}
						$activity['action_allowed']=implode(" ", $action_allowed);
					}
					$activity->save();
				}
			}
		}
	}

	function comment($posting_model,$msg){

		if(! $posting_model instanceof xepan/marketing\Model_SocialPosting and !$posting_model->loaded())
			throw $this->exception('Posting Model must be a loaded instance of Model_SocialPosting','Growl');

		$user_model = $posting_model->ref('user_id');
		$config_model = $user_model->ref('config_id');

		$this->setup_client($config_model->id);

  		$client = $this->client;
  		$client->access_token = $user_model['access_token'];
		$client->access_token_secret = $user_model['access_token_secret'];

		$parameters = new \stdClass;

		if($posting_model['post_type']=='shares'){
			// echo "i m share";
			$parameters->comment = $msg;
			$success = $client->CallAPI(
					'http://api.linkedin.com/v1/people/~/network/updates/key='.$posting_model['postid_returned'].'/update-comments?format=json',
					'POST', $parameters, array('FailOnAccessError'=>true, 'RequestContentType'=>'application/json'), $likes_comments, $headers);
		}else{
			// 	Group Post

			$post_id_array = explode("/",$posting_model['group_id']);
	  		$post_id= $post_id_array[count($post_id_array)-1];

			$parameters->text = $msg;
			$success = $client->CallAPI(
					'http://api.linkedin.com/v1/posts/'.$post_id.'/comments?format=json',
					'POST', $parameters, array('FailOnAccessError'=>true, 'RequestContentType'=>'application/json'), $likes_comments, $headers);
		}
		$success = $client->Finalize($success);

	}

	function get_post_fields_using(){
		return array('title','url','image','255');
	}
}