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
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
		$redirect_url = 'http://'.$protocol.$_SERVER['PHP_SELF'].'?page=xepan_marketing_socialafterloginhandler&xfrom=Linkedin&client_config_id='.$config_model->id;
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

		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
		$redirect_url = $protocol.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?page=xepan_marketing_socialafterloginhandler&xfrom=Linkedin&client_config_id='.$config_model->id;
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
		// $linkedin_user['access_token_secret'] = $this->client->access_token_secret;
		$linkedin_user['access_token_expiry'] = $token_expires;
		$linkedin_user->save();		
		return true;
	}


	function config_page(){

		$c = $this->owner->add('xepan\hr\CRUD',
							['frame_options'=>['width'=>'600px'],'entity_name'=>"LinkedIn App"],
							null,
							['view/social/config']);
		$model = $this->add('xepan/marketing/SocialPosters_Linkedin_LinkedinConfig');
		$c->setModel($model,['name','appId','secret','post_in_groups','filter_repeated_posts','status']);
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

		if($user_model['post_on_timeline']){
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
			$social_posting['status'] = "Posted";
			$social_posting->save();
		}

		//  geting all postable pages
		$postable_page = $user_model->getLinkedinCompany();
		$page_responses = [];

///////////////////////////////////////////////////////////////////////////////////
// post on all postable pages
		foreach ($postable_page as $page_id => $page_info) {
			// https://api.linkedin.com/v1/companies/{id}/shares?format=json
		  	$response = $client->request( 'POST','/v1/companies/'.$page_id.'/shares?format=json', 
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
			$page_responses[] = $response_data;
		}

		foreach ($page_responses as $page_response) {
			$social_posting = $this->add('xepan\marketing\Model_SocialPosters_Base_SocialPosting');
			$social_posting['user_id'] = $user_model['id'];
			$social_posting['post_id'] = $post_model['id'];
			$social_posting['campaign_id'] = $campaign_id;
			$social_posting['post_type'] = "Page_Share";
			$social_posting['postid_returned'] = $page_response->updateKey;
			$social_posting['posted_on'] = $this->app->now;
			$social_posting['return_data'] = json_encode($page_response);
			$social_posting['status'] = "Posted";
			$social_posting->save();
		}
//end of postable page
///////////////////////////////////////////////////////////////////////////////
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
		if(! $posting_model instanceof \xepan\marketing\Model_SocialPosting and !$posting_model->loaded())
			throw $this->exception('Posting Model must be a loaded instance of Model_SocialPosting','Growl');
		
		$user_model = $posting_model->ref('user_id');
		$config_model = $user_model->ref('config_id');

		$this->setup_client($config_model,$user_model);

  		$client = $this->client;
  		$client->access_token = $user_model['access_token'];
		$client->access_token_secret = $user_model['access_token_secret'];

		
		// get comment and like of share of company page
		if( explode("_",$posting_model['post_type'])[0] === "Page" ){
			$company_id = explode("-",$posting_model['postid_returned']);
			$company_id = substr($company_id[1], 1);
			// to get only comments
			// $path = '/v1/companies/'.$company_id.'/updates/key='.$posting_model['postid_returned'].'/updat-comment?format=json';
			$path = '/v1/companies/'.$company_id.'/updates/key='.$posting_model['postid_returned'].'/?format=json';
		}else{
			throw new \Exception("on wall of linkedini page todo ");
		}
		
		$client = new \GuzzleHttp\Client(['base_uri'=>'https://api.linkedin.com']);
		$response = $client->request( 'GET',$path,
						[
	        				'headers' => [
	        							"Authorization" => "Bearer " . $user_model['access_token'],
	                    				"Content-Type" => "application/json",
	                            		"x-li-format"=>"json"
	                            	],
	        				'client_id' => $config_model['app_id'],
	    				]);
		$stream = $response->getBody();
		// std class object
		$response_data = json_decode((string)$stream);

		$response_comments = $response_data->updateComments;
		$response_likes = $response_data->likes;
		
		// echo "<pre>";
		// print_r($response_data);
		// updating comments
		if(isset($response_comments->values)){
			$comments = $response_comments->values;
			foreach ($comments as $key => $comment) {
				$activity = $this->add('xepan/marketing/Model_SocialPosters_Base_SocialActivity');
				$activity->addCondition('posting_id',$posting_model->id);
				$activity->addCondition('activityid_returned',$comment->id);
				$activity->addCondition('activity_type',"Comment");
				$activity->tryLoadAny();

				// converting milisecond time stamp to date format
				$mil = $comment->timestamp;
				$seconds = $mil / 1000;
				$date = date("Y-m-d H:i:s", $seconds);

				$activity['activity_on'] = $date;

				$activity['name'] = $comment->comment;

				if(isset($comment->person)){
					$activity['activity_by'] = $comment->person->id." - ".$comment->person->firstName." ".$comment->person->lastName." ( ".$comment->person->headline." )";
				}elseif(isset($comment->company)){
					$activity['activity_by'] = $comment->company->id." - ".$comment->company->name;
				}
				$activity->save();
			}
		}

		// updating likes
		$posting_model->updateLikesCount($response_likes->_total);

	}

	function comment($posting_model,$msg){

		if(! $posting_model instanceof \xepan\marketing\Model_SocialPosters_Base_SocialPosting and !$posting_model->loaded())
			throw $this->exception('Posting Model must be a loaded instance of Model_SocialPosting');

		$user_model = $posting_model->ref('user_id');
		$config_model = $user_model->ref('config_id');

		$this->setup_client($config_model,$user_model);

  		$client = $this->client;
  		$client->access_token = $user_model['access_token'];
		$client->access_token_secret = $user_model['access_token_secret'];


		$parameters = new \stdClass;
		// $parameters->visibility = new \stdClass;
		// $parameters->visibility->code = 'anyone';
  		$parameters->comment = $msg;
  		
  		if( explode("_",$posting_model['post_type'])[0] === "Page" ){
			$company_id = explode("-",$posting_model['postid_returned']);
			$company_id = substr($company_id[1], 1);
			// https://api.linkedin.com/v1/companies/2414183/updates/key=UPDATE-c2414183-5986959985467285504/update-comments-as-company/
			$url = '/v1/companies/'.$company_id.'/updates/key='.$posting_model['postid_returned'].'/update-comments-as-company';
		}else{
			throw new \Exception("comment only applied on page share");
		}

		$body_json = json_encode($parameters, true);
		$client = new \GuzzleHttp\Client(['base_uri' => 'https://api.linkedin.com']);
		$response = $client->request( 'POST',$url,
						[
	        				'headers'=> [
	        							"Authorization" => "Bearer " . $user_model['access_token'],
	                    				"Content-Type" => "application/json",
	                            		"x-li-format"=>"json"
	                            	],
	        				'client_id' => $config_model['app_id'],
	       	 				'body'      => $body_json
	    				]);

		// $stream = $response->getBody();
		// // std class object
		// $response_data = json_decode((string)$stream);
		$this->updateActivities($posting_model);
	}

	function get_post_fields_using(){
		return array('title','url','image','255');
	}

	function getCompany($config_model,$user_model){

		if( !$config_model instanceof \xepan\marketing\SocialPosters_Linkedin_LinkedinConfig)
			throw new \Exception("must pass instance of Linkedin Config");
		
		if( !$user_model instanceof \xepan\marketing\Model_SocialPosters_Base_SocialUsers)
			throw new \Exception("must pass instance of social user");

		$client = new \GuzzleHttp\Client(['base_uri' => 'https://api.linkedin.com']);
		$response = $client->request( 'Get','/v1/companies?format=json&is-company-admin=true',
							[
		        				'headers'=> [
		        							"Authorization" => "Bearer " . $user_model['access_token'],
		                    				"Content-Type" => "application/json",
		                            		"x-li-format"=>"json"
		                            	],
		        				'client_id' => $config_model['appId']
		    				]);
		$stream = $response->getBody();
		// std class object
		$response_data = json_decode((string)$stream);
		return $response_data;
	}
}