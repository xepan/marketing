<?php

namespace xepan\marketing;

class Model_Campaign_SocialUser_Association extends \Model_Table{
	public $table = "campaign_socialuser_association";
	function init(){
		parent::init();

		$this->hasOne('xepan/marketing/SocialUser','socialuser_id');
		$this->hasOne('xepan/marketing/Campaign','campaign_id');		

		$this->addExpression('userid_returned')->set($this->refSQL('socialuser_id')->fieldQuery('userid_returned'));
		$this->addExpression('is_active')->set($this->refSQL('socialuser_id')->fieldQuery('is_active'));
		$this->addExpression('type')->set($this->refSQL('socialuser_id')->fieldQuery('type'));
	}
} 