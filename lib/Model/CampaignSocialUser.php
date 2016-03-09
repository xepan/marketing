<?php

namespace xepan\marketing;  

class Model_CampaignSocialUser extends \Model_Document{

	function init(){
		parent::init();

		$this->hasOne('xepan/marketing/SocialUser','Socialuser_id');
		$this->hasOne('xepan/marketing/Campaign','Campaign_id');		

	}
} 