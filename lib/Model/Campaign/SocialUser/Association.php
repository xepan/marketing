<?php

namespace xepan\marketing;

class Model_Campaign_SocialUser_Association extends \Model_Table{
	public $table = "campaign_socialuser_association";
	function init(){
		parent::init();

		// $this->hasOne('xepan/marketing/SocialUser','socialuser_id');
		$this->hasOne('xepan/marketing/Campaign','campaign_id');		

	}
} 