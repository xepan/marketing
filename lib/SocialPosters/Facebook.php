<?php

namespace xepan\marketing;

// EXTRA  MODELS DEFINED AT THE BOTTOM OF THIS FILES

class SocialPosters_Facebook extends SocialPosters_Base_Social {
	public $fb=null;
	public $config=null;
	public $default_graph_version = "v2.6";

	function init(){
		parent::init();
		
		// require_once(getcwd().'/../vendor/xepan/marketing/lib/SocialPosters/Facebook/FacebookConfig.php');
		// require_once(getcwd().'/../vendor/xepan/marketing/lib/SocialPosters/Facebook/FacebookPosting.php');
		// require_once(getcwd().'/../vendor/xepan/marketing/lib/SocialPosters/Facebook/FacebookUsers.php');
		// require_once(getcwd().'/../vendor/xepan/marketing/lib/SocialPosters/Facebook/facebook.php');
		
	}

	function login_status(){
		$config_model = $this->add('xepan/marketing/SocialPosters_Facebook_FacebookConfig');
		$config_model->tryLoad($_GET['for_config_id']);

		if(!$config_model->loaded()){
			$this->add('View_Error')->set('Could not load Config Model');
			return false;
		}

		$config = array(
		      'app_id' => $config_model['appId'],
		      'app_secret' => $config_model['secret'],
		      'default_graph_version' => $this->default_graph_version
		  );
	      // 'fileUpload' => true, // optional
	      // 'allowSignedRequest' => false, // optional, but should be set to false for non-canvas apps
		$this->fb = $facebook = new \Facebook\Facebook($config);

		if(!$this->fb){
			echo "Configuration Problem";
			return false;
		}
		
		$helper = $facebook->getRedirectLoginHelper();
		
		$permissions = [
			'email',
			'user_hometown',
			'user_religion_politics',
			'publish_actions',
			'user_likes',
			'user_status',
			'user_about_me',
			'user_location',
			'user_tagged_places',
			'user_birthday',
			'user_photos',
			'user_videos',
			'user_education_history',
			'user_posts',
			'user_website',
			'user_friends',
			'user_relationship_details',
			'user_work_history',
			'user_games_activity',
			'user_relationships',
			'user_managed_groups',
			'publish_pages',
			'user_managed_groups',
			'user_events',
			'rsvp_event',
			'read_insights',
			'read_custom_friendlists',
			'manage_pages',
			'pages_show_list',
			'read_page_mailboxes',
			'pages_show_list',
			'pages_manage_instant_articles'

		];

		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
		$redirect_url = $protocol.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?page=xepan_marketing_socialafterloginhandler&xfrom=Facebook&for_config_id='.$config_model->id;
		$loginUrl = $helper->getLoginUrl($redirect_url, $permissions);
		
		return '<a href="' . $loginUrl . '">Log in with Facebook!</a>';
		
		// $user_id = $this->fb->getUser();
		// if(!$user_id){
		// 	$login_url = $this->fb->getLoginUrl(
		// 								array(
		// 									'scope'=>'public_profile,user_friends,email,user_about_me,user_education_history,user_events,user_hometown,user_likes,user_location,user_managed_groups,user_photos,user_posts,user_tagged_places,user_videos,read_custom_friendlists,read_insights,read_audience_network_insights,read_page_mailboxes,manage_pages,publish_pages,publish_actions,rsvp_event,pages_show_list,pages_manage_cta,pages_manage_instant_articles,ads_read,ads_management,pages_messaging,pages_messaging_phone_number',
		// 									'redirect_uri'=>'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?page=xepan_marketing_socialafterloginhandler&xfrom=Facebook&for_config_id='.$config_model->id
		// 									));
			
		//   	echo '<a class="btn btn-danger btn-xs" href="'.$login_url.'">Login</a>';
		// }else{
		// 	if($this->after_login_handler())
		// 		$this->add('View_Info')->set('Access Token Updated');
		// 	else
		// 		$this->add('View_Error')->set('Access Token Not Updated');

		// 	// $this->config['userid_returned'] = $user_id;
		// 	// $this->config->save();
		//  //  	return '<a class="btn btn-success btn-xs" href="#" onclick="javascript:'.$this->owner->js()->reload(array('facebook_logout'=>1)).'">Logout</a>';
		// }

	}

	function after_login_handler(){
		$config_model = $this->add('xepan/marketing/SocialPosters_Facebook_FacebookConfig');
		$config_model->tryLoad($_GET['for_config_id']);

		if(!$config_model->loaded()){
			$this->add('View_Error')->set('Could not load Config Model');
			return false;
		}
		
		$config = array(
		      'app_id' => $config_model['appId'],
		      'app_secret' => $config_model['secret'],
		      'fileUpload' => true, // optional
		      'allowSignedRequest' => false, // optional, but should be set to false for non-canvas apps
		  );

		$this->fb = $facebook = $fb = new \Facebook\Facebook($config);

		if(!$this->fb){
			return "Configuration Problem";
		}

		$helper = $fb->getRedirectLoginHelper();
		try {
		  $accessToken = $helper->getAccessToken();
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
		  // When Graph returns an error
		  echo 'Graph returned an error: ' . $e->getMessage();
		  exit;
		} catch(Facebook\Exceptions\FacebookSDKException $e) {
		  // When validation fails or other local issues
		  echo 'Facebook SDK returned an error: ' . $e->getMessage();
		  exit;
		}

		if (isset($accessToken)) {
		  // Logged in!
		  $_SESSION['facebook_access_token'] = (string) $accessToken;
			
			$oAuth2Client = $fb->getOAuth2Client();
			// Exchanges a short-lived access token for a long-lived one
			$longLivedAccessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
			
			$fb->setDefaultAccessToken($longLivedAccessToken);
			try {
			  $response = $fb->get('/me');
			  $userNode = $response->getGraphUser();
			} catch(Facebook\Exceptions\FacebookResponseException $e) {
			  // When Graph returns an error
			  echo 'Graph returned an error: ' . $e->getMessage();
			  exit;
			} catch(Facebook\Exceptions\FacebookSDKException $e) {
			  // When validation fails or other local issues
			  echo 'Facebook SDK returned an error: ' . $e->getMessage();
			  exit;
			}

			$fb_user = $this->add('xepan/marketing/SocialPosters_Facebook_FacebookUsers');
			$fb_user->addCondition('userid_returned',$userNode->getId());
			$fb_user->addCondition('config_id',$config_model->id);
			$fb_user->tryLoadAny();
			$fb_user['name'] = $userNode['name'];
			$fb_user['access_token'] = (string)$longLivedAccessToken;
			$fb_user['is_access_token_valid']= true;
			$fb_user->save();
			return true;
		}
		return false;
	}


	function config_page(){
		$c = $this->owner->add('xepan\hr\CRUD',
							['frame_options'=>['width'=>'600px'],'entity_name'=>"Facebook App"],
							null,
							['view/social/config']);
		$model = $this->add('xepan/marketing/SocialPosters_Facebook_FacebookConfig');
		$c->setModel($model,['name','appId','secret','post_in_groups','filter_repeated_posts','status']);
	}

	function postSingle($user_model,$params,$post_in_groups=true, $groups_posted=array(),$under_campaign_id=0){
		if(! $user_model instanceof xepan\marketing\Model_SocialPosters_Base_SocialUsers AND !$user_model->loaded()){
			throw $this->exception('User must be a loaded model of Social User Type','Growl');
		}

		$post_content=array();
	  		
  		$api='feed';
  		if($params['title']) $post_content['title'] = $params['title'];
  		if($params['url']) $post_content['link'] = $params['url'];

  		// throw new \Exception($this->api->url()->absolute()->getUrl());
  		if($params['first_image']){
  			if(!$params['url']) $api='photos';
  			$img = str_replace($this->app->pathfinder->base_location->base_url, "", $params['first_image']);
  			die($img);
  			$post_content['attached_files'] = '@'.$img;
  		} 

  		if($params['message_255']) $post_content['message'] = $params['message_255'];
  		$post_content['access_token'] = $user_model['access_token'];

  		$config_model = $user_model->ref('config_id');

  		$config = array(
				      'appId' => $config_model['appId'],
				      'secret' => $config_model['secret'],
				      'fileUpload' => true, // optional
				      'allowSignedRequest' => false, // optional, but should be set to false for non-canvas apps
				  );

  		
  		$this->fb = $facebook = new \Facebook($config);
  		// $this->fb = $facebook = $this->add('xepan\marketing\SocialPosters_Facebook_facebook',$config);
		$this->fb->setFileUploadSupport(true);

		$ret_obj = $this->fb->api('/'. $user_model['userid_returned'] .'/'.$api, 'POST',
			  								$post_content
		                                 );
		$social_posting_save = $this->add('xepan/marketing/Model_SocialPosters_Base_SocialPosting');
		$social_posting_save->create($user_model->id, $params->id, $ret_obj['id'], 'Status Update', 0,"", $under_campaign_id);

		if($post_in_groups){
			$groups = $this->fb->api('/'. $user_model['userid_returned'] .'/groups', 'GET',array('access_token'=>$user_model['access_token']));
			print_r($groups);
			foreach ($groups['data'] as $grp) {
				if(!in_array($grp['id'],$groups_posted)  OR !$config_model['filter_repeated_posts']){
			  		try{
			  			$ret_obj = $this->fb->api('/'. $grp['id'] .'/'.$api, 'POST',$post_content);
			  			$social_posting_save->create($user_model->id, $params->id, $ret_obj['id'], 'Group Post', $grp['id'], $grp['name'], $under_campaign_id);
				  		$groups_posted[] = $grp['id'];
			  		}catch(\Exception $e){
			  			continue;
			  		}
	  			}
	  		}
		}

		/*
	single post return obj 
		Array ( [id] => 1518888648358366_1533808290199735 ) 
	groups object
		Array ( [data] => Array ( [0] => Array ( [name] => Xavoc [bookmark_order] => 1 [id] => 274814329235304 ) ) [paging] => Array ( [next] => https://graph.facebook.com/v2.2/1518888648358366/groups?icon_size=16&limit=5000&offset=5000&__after_id=enc_Aez2nUTN78cytXqKvhbxXh4ViCEVLM0tPMjot6flXqvQGqVi7S9NBj5JqD9ckHORo533WmjCyiWZM_erXbC6oPlK ) ) 
		*/

	}

	function postAll($postable_and_user_data){ 

		// echo $temp['user_obj']['userid_returned'] ." post==".$temp['post_id'];
		foreach ($postable_and_user_data as $temp) {
			$batch_array = [];

			$fb = new \Facebook\Facebook([
			  'app_id' => $temp['config_obj']['appId'],
			  'app_secret' => $temp['config_obj']['secret'],
			  'default_graph_version' => $this->default_graph_version,
			  ]);

			$fb->setDefaultAccessToken($temp['user_obj']['access_token']);
			//use link post
			$data = [];
			if($temp['post_image_path']){
				//use photo with message
				// $data = [
				//   'message' => 'My awesome photo upload example.',
				//   'source' => $fb->fileToUpload('/path/to/photo.jpg'),
				// ];
				$data['message'] = $temp['post_obj']['message_blog'];
				$data['source'] = $fb->fileToUpload($temp['post_image_path']);
				$post_type = $end_point = "photos";
			}else{
					// $linkData = [
					//   'link' => 'http://www.example.com',
					//   'message' => 'User provided message',
					//   ];
				$data['message'] = strip_tags($temp['post_obj']['message_blog']);
				$post_type = $end_point = "feed";
				if($temp['post_obj']['url']){
					$data['link'] = $temp['post_obj']['url'];
					$post_type = "link";
				}
			}

			// get all Postable Pages ['page_id'] = ['name'=>"some thing","access_token"=>"page token"] etc.
			$user_model = $temp['user_obj'];
			$postable_page = $user_model->getFBPages($only_postable_page=true);
			
			try {
			  // Returns a `Facebook\FacebookResponse` object
				if($user_model['post_on_timeline'])
			  		$response = $fb->post('/me/'.$end_point, $data, $temp['user_obj']['access_token']);
			  
			  $page_responses = [];
			  // post on all postable pages
			  foreach ($postable_page as $page_id => $page_info) {
			  		$page_responses[] = $fb->post('/'.$page_id."/".$end_point,$data,$page_info['access_token']);
			  }

			} catch(Facebook\Exceptions\FacebookResponseException $e) {
			  echo 'Graph returned an error: ' . $e->getMessage();
			} catch(Facebook\Exceptions\FacebookSDKException $e) {
			  echo 'Facebook SDK returned an error: ' . $e->getMessage();
			}


			if($user_model['post_on_timeline']){
				$graphNode = $response->getGraphNode();

				//todo save social posting record into database
				$social_posting = $this->add('xepan\marketing\Model_SocialPosters_Base_SocialPosting');
				$social_posting['user_id'] = $temp['user_id'];
				$social_posting['post_id'] = $temp['post_id'];
				$social_posting['campaign_id'] = $temp['campaign_id'];
				$social_posting['post_type'] = $post_type;
				$social_posting['postid_returned'] = $graphNode['id'];
				$social_posting['posted_on'] = $this->app->now;
				$social_posting['status'] = "Posted";
				$social_posting->save();
			}

			foreach ($page_responses as $page_response) {
				$graphNode = $page_response->getGraphNode();
				//todo save social posting record into database
				$social_posting = $this->add('xepan\marketing\Model_SocialPosters_Base_SocialPosting');
				$social_posting['user_id'] = $temp['user_id'];
				$social_posting['post_id'] = $temp['post_id'];
				$social_posting['campaign_id'] = $temp['campaign_id'];
				$social_posting['post_type'] = "Page_".$post_type; // for recognization of posting page 
				$social_posting['postid_returned'] = $graphNode['id'];
				$social_posting['posted_on'] = $this->app->now;
				$social_posting['status'] = "Posted";
				$social_posting->save();	
			}
			//update posting_on in schedule table
			if($temp['schedule_id']){
				$schedule = $this->add('xepan\marketing\Model_Schedule')->tryLoad($temp['schedule_id']);
				$schedule['posted_on'] = $this->app->now;
				$schedule->save();
			}
		}

	}

	function get_post_fields_using(){
		return array('title','url','image','255');
	}

	function icon($only_css_class=false){
		if($only_css_class) 
			return "fa fa-facebook";
		return "<i class='fa fa-facebook'></i>";
	}

	function profileURL($user_id_pk, $other_user_id=false){
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

		return array('url'=>"https://www.facebook.com/app_scoped_user_id/". $other_user_id ."/",'name'=>$name);
		// return "https://www.facebook.com/profile.php?id=".$user['userid'];
	}

	function postURL($post_id_returned){
		$post = $this->add('xepan/marketing/Model_SocialPosters_Base_SocialPosting')->tryLoadBy('postid_returned',$post_id_returned);
		if(!$post->loaded()) return false;
		
		$user= $post->ref('user_id');
		if(!$user['userid']) return false;
		
		$post_id_returned = explode("_", $post_id_returned);
		if(count($post_id_returned) !=2) return false;

		$post_id_returned = $post_id_returned[1];

		return "https://www.facebook.com/permalink.php?story_fbid=".$post_id_returned."&id=".$user['userid'];
		throw $this->exception('Define in extnding class');
	}

	function groupURL($group_id){
		throw $this->exception('Define in extnding class');
	}

	function updateActivities($posting_model){
		if(! $posting_model instanceof \xepan\marketing\Model_SocialPosting and !$posting_model->loaded())
			throw $this->exception('Posting Model must be a loaded instance of Model_SocialPosting','Growl');
				
		$user_model = $posting_model->ref('user_id');

		$config_model = $user_model->ref('config_id');

  		$config = array(
		      'app_id' => $config_model['appId'],
		      'app_secret' => $config_model['secret'],
		      'default_graph_version' => $this->default_graph_version
		  );

  		$post_content['access_token'] = $user_model['access_token'];

  		$fb = new \Facebook\Facebook([
		  'app_id' => $config_model['appId'],
		  'app_secret' => $config_model['secret'],
		  'default_graph_version' => $this->default_graph_version
		]);

  		$fb->setDefaultAccessToken($user_model['access_token']);
  		$requests = [
		  'reactions' => $fb->request('GET', '/'.$posting_model['postid_returned'].'/reactions', [], $user_model['access_token']),
		  'comments' => $fb->request('GET', '/'.$posting_model['postid_returned'].'/comments?filter=stream&fields=parent.fields(id),created_time,message,from,likes', [], $user_model['access_token'])
		];

		try {
		  $responses = $fb->sendBatchRequest($requests);
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
		  echo 'Graph returned an error: ' . $e->getMessage();
		  exit;
		} catch(Facebook\Exceptions\FacebookSDKException $e) {
		  echo 'Facebook SDK returned an error: ' . $e->getMessage();
		  exit;
		}

		// decode all the data
		$response_data = $responses->getDecodedBody();
		
		// index 0 for reactions because first batch process is reaction
		if($response_data[0]['code'] === 200){
			$reactions_data_array = json_decode($response_data[0]['body'],true);
			$posting_model->updateLikesCount(count($reactions_data_array['data']));
		}
		
		// index 1 for comments because second batch process is comments
		if($response_data[1]['code'] === 200){
			$comments_data_array = json_decode($response_data[1]['body'],true);
			$comments = $comments_data_array['data'];
			// echo "<pre>";
			foreach ($comments as $key => $comment) {
				$activity = $this->add('xepan/marketing/Model_SocialPosters_Base_SocialActivity');
				$activity->addCondition('posting_id',$posting_model->id);
				$activity->addCondition('activityid_returned',$comment['id']);
				$activity->addCondition('activity_type',"Comment");
				$activity->tryLoadAny();

				$activity['activity_on']=$comment['created_time'];
				$activity['activity_by']=$comment['from']['id'].'-'.$comment['from']['name'];
				$activity['name']=$comment['message'];
				if($comment['likes']['data'])
					$activity['name'] = $activity['name'] . '<br><i class="fa fa-thumbs-up">'.count($comment['likes']['data']).'</i>';
				$activity['action_allowed']=$comment['can_remove']?'can_remove':'';
				$activity->save();
			}
			
		}

	}

	function comment($posting_model,$msg){

		if(! $posting_model instanceof \xepan\marketing\Model_SocialPosters_Base_SocialPosting and !$posting_model->loaded())
			throw $this->exception('Posting Model must be a loaded instance of Model_SocialPosting','Growl');

		$user_model = $posting_model->ref('user_id');

		$config_model = $user_model->ref('config_id');

		$fb = new \Facebook\Facebook([
			'app_id' => $config_model['appId'],
			'app_secret' => $config_model['secret'],
			'default_graph_version' => $this->default_graph_version,
		]);

		$fb->setDefaultAccessToken($user_model['access_token']);

		$data['message'] = $msg;

		try {
		  	$response = $fb->post('/'.$posting_model['postid_returned'].'/comments/', $data, $user_model['access_token']);
			} catch(Facebook\Exceptions\FacebookResponseException $e) {
			  echo 'Graph returned an error: ' . $e->getMessage();
			} catch(Facebook\Exceptions\FacebookSDKException $e) {
			  echo 'Facebook SDK returned an error: ' . $e->getMessage();
		}

		$this->updateActivities($posting_model);

		// $this->app->memorize('comment',$response);
		// var_dump($response);
	}

	function getPage($user_model){
		if( !$user_model instanceof \xepan\marketing\Model_SocialPosters_Base_SocialUsers)
			throw new \Exception("must pass instance of social user");

		// get Facebook Config 
		$config_model = $user_model->appConfig();
		$config = array(
		      'app_id' => $config_model['appId'],
		      'app_secret' => $config_model['secret'],
		      'default_graph_version' => $this->default_graph_version
		  );

		$this->fb = $facebook = $fb = new \Facebook\Facebook($config);
		if(!$this->fb){
			return "Configuration Problem";
		}
		
		$fb->setDefaultAccessToken($user_model['access_token']);
		// get all  pages 
		// https://graph.facebook.com/$user_id_returned/accounts/?access_token=$access_token
		$requests = [
					$facebook->request('GET', '/'.$user_model['userid_returned'].'/accounts')
				];
 		try {
 			$responses = $fb->sendBatchRequest($requests);
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
		  echo 'Graph returned an error: ' . $e->getMessage();
		  exit;
		} catch(Facebook\Exceptions\FacebookSDKException $e) {
		  echo 'Facebook SDK returned an error: ' . $e->getMessage();
		  exit;
		}

		// decode all the data
		$response_data = $responses->getDecodedBody();		
		if($response_data[0]['code'] === 200){
			return $response_data[0]['body'];
		}else
			throw new \Exception("some thing wrong return code = ".$response_data[0]['code']);
		
	}
}
