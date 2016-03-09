<?php

namespace xepan\marketing;  

class Model_CampaignSocialUser extends \Model_Document{

	function init(){
		parent::init();

		$this->hasOne('xepan/marketing/SocialUser','Social_user_id');		

	}
} 