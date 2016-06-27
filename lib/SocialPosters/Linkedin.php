<?php

namespace xepan\marketing;

// EXTRA  MODELS DEFINED AT THE BOTTOM OF THIS FILES

class SocialPosters_Linkedin extends SocialPosters_Base_Social{
	public $client=null;
	public $client_config=null;

	function init(){
		parent::init();
	}

	function setup_client($config_model,$user_model){

		// $config_model = $this->add('xepan/marketing/SocialPosters_Linkedin_LinkedinConfig')->load($_GET['for_config_id']);
		$redirect_url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?page=xepan_marketing_socialafterloginhandler&xfrom=Linkedin&client_config_id='.$config_model->id;
		$this->client = new \LinkedIn\LinkedIn(
		  array(
		    'api_key' => $config_model['appId'], 
		    'api_secret' => $config_model['secret'], 
		    'callback_url' => $redirect_url
		  )
		);
		$this->client->setAccessToken($user_model['access_token']);
	}

	function login_status(){
		// $this->setup_client($_GET['for_config_id']);
		$config_model = $this->add('xepan/marketing/SocialPosters_Linkedin_LinkedinConfig')->load($_GET['for_config_id']);
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
		$redirect_url = $protocol.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?page=xepan_marketing_socialafterloginhandler&xfrom=Linkedin&client_config_id='.$config_model->id;
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
		    // \LinkedIn\LinkedIn::SCOPE_FULL_PROFILE,
		    // \LinkedIn\LinkedIn::SCOPE_NETWORK,
		    // \LinkedIn\LinkedIn::SCOPE_CONTACT_INFO,
		    \LinkedIn\LinkedIn::SCOPE_EMAIL_ADDRESS,
		    // \LinkedIn\LinkedIn::SCOPE_READ_WRITE_GROUPS,
		    // \LinkedIn\LinkedIn::SCOPE_READ_WRTIE_UPDATES,
		    // \LinkedIn\LinkedIn::SCOPE_WRITE_MESSAGES
		    'w_share',
		    'rw_company_admin'
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
			$this->add('View')->set("linkedin code not found");
			$this->add('View')->set($_GET['error_description']);
			$this->add('View')->set($_GET['error']);
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
		
		// $users_crud = $c->addRef('xepan/marketing/Model_SocialPosters_SocialUsers',array('label'=>'Users'));

		if($c->grid){
			$f=$c->addFrame('Login URL');
			if($f){
				$config_model = $this->add('xepan/marketing/SocialPosters_Linkedin_LinkedinConfig');
				$config_model->load($c->id);

				$f->add('View')->setElement('a')->setAttr('href','index.php?page=xepan_marketing_socialloginmanager&social_login_to=Linkedin&for_config_id='.$config_model->id)->setAttr('target','_blank')->set('index.php?page=xepan_marketing_socialloginmanager&social_login_to=Linkedin&for_config_id='.$config_model->id);
			}
		}
	}


	function icon($only_css_class=false){
		if($only_css_class) 
			return "fa fa-linkedin";
		return "<i class='fa fa-linkedin'></i>";
	}

	function postAll($postable_and_user_data){ // all social post row as hash array or model
		foreach ($postable_and_user_data as $temp) {
			$this->postSingle($temp['user_obj'],$temp['post_obj'],$temp['config_obj'],$temp['post_image_path'],$temp['campaign_id'],$temp['schedule_id']);
		}
	}

	function postSingle($user_model,$post_model,$config_model,$post_image_path,$campaign_id,$schedule_id){
		$activity_type = "shares";

		if(! $user_model instanceof xepan\marketing\Model_SocialPosters_Base_SocialUsers AND !$user_model->loaded())
			throw $this->exception('User must be a loaded model of Social User Type');
		
		if($post_model instanceof \xepan\marketing\Model_SocialPost AND !$post_model->loaded())
			throw $this->exception('Post must be a loaded model of Social Post');

		if($config_model instanceof \xepan\marketing\Model_SocialPosters_Base_SocialConfig AND !$config_model->loaded())
			throw $this->exception('Config must be loaded model of social config type');
					
		if(!$campaign_id)
			throw new \Exception("campaign id must be defined");
		
		if(!$schedule_id)
			throw new \Exception("schedule id must be defined");

		$this->setup_client($config_model,$user_model);

		$parameters = new \stdClass;
		$parameters->visibility = new \stdClass;
		$parameters->visibility->code = 'anyone';
  		if($post_model['message_blog']) 
  			$parameters->comment = strip_tags($post_model['message_blog']);
  		
		if($post_model['url']){
			$parameters->content = new \stdClass;
	  		$parameters->content->{'submitted-url'} = $post_model['url'];

	  		if($post_model['title'])
	  			$parameters->content->title = $post_model['title'];

	  		if($post_image_path){
	  			$parameters->content->{'submitted-image-url'} = $post_image_path;
	  		}
		}
		
		$body_json = json_encode($parameters, true);
		$client = new \GuzzleHttp\Client(['base_uri' => 'https://api.linkedin.com']);
		$response = $client->request( 'POST','/v1/people/~/shares?format=json', 
							[
		        				'headers'=> [
		        							"Authorization" => "Bearer " . $user_model['access_token'],
		                    				"Content-Type" => "application/json",
		                            		"x-li-format"=>"json"
		                            	],
		        				'client_id' => $config_model['app_id'],
		       	 				'body'      => $body_json
		    				]);

		// $_SESSION['POSTRESPONSE'] = $response;
		$stream = $response->getBody();
		// std class object
		$response_data = json_decode((string)$stream);
		// $this->app->memorize('response_data',$response_data);
		// $response = $this->app->recall("response_data");

		//todo save social posting record into database
		$social_posting = $this->add('xepan\marketing\Model_SocialPosters_Base_SocialPosting');
		$social_posting['user_id'] = $user_model['id'];
		$social_posting['post_id'] = $post_model['id'];
		$social_posting['campaign_id'] = $campaign_id;
		$social_posting['post_type'] = "Share";
		$social_posting['postid_returned'] = $response_data->updateKey;
		$social_posting['posted_on'] = $this->app->now;
		$social_posting->save();

		if($schedule_id){
			$schedule = $this->add('xepan\marketing\Model_Schedule')->tryLoad($schedule_id);
			$schedule['posted_on'] = $this->app->now;
			$schedule->save();
		}

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