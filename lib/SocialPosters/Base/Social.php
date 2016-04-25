<?php

namespace xepan\marketing;

class SocialPosters_Base_Social extends \AbstractController{

	function init(){
		parent::init();

		// require_once(getcwd().'/../vendor/xepan/marketing/lib/SocialPosters/Base/SocialActivity.php');
		// require_once(getcwd().'/../vendor/xepan/marketing/lib/SocialPosters/Base/SocialConfig.php');
		// require_once(getcwd().'/../vendor/xepan/marketing/lib/SocialPosters/Base/SocialPost.php');
		// require_once(getcwd().'/../vendor/xepan/marketing/lib/SocialPosters/Base/SocialUsers.php');

	}

	function login_status(){
		return "Oops";
	}

	function config_page(){
		echo "Oops";
	}

	function get_post_fields_using(){
		return array('title','image','255');
	}

	function postSingle($user_model,$params,$post_in_groups=true, &$groups_posted=array(),$under_campaign_id=0){
		throw $this->exception('Define in extnding class');
	}

	function postAll($params){
		throw $this->exception('Define in extnding class');
		
	}

	function icon($only_css_class=false){
		throw $this->exception('Define in extnding class');
	}

	function profileURL($user_id){
		throw $this->exception('Define in extnding class');
	}

	function postURL($post_id){
		throw $this->exception('Define in extnding class');
	}

	function groupURL($group_id){
		throw $this->exception('Define in extnding class');
	}

	function updateActivities($posting_model){
		throw $this->exception('Define in extnding class');
	}

	function comment($posting_model){
		throw $this->exception('Define in extnding class');
	}

}