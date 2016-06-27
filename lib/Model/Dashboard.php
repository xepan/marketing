<?php

namespace xepan\marketing;

class Model_Dashboard extends \xepan\marketing\Model_Campaign{
	function init(){
		parent::init();

		$this->addExpression('lead_count')->set("'ToDo'");
		$this->addExpression('response')->set("'ToDo'");

		$this->addExpression('newsletter_count')->set("'ToDo'");
		$this->addExpression('nwl_opp_count')->set("'ToDo'");
		$this->addExpression('sms_count')->set("'ToDo'");
		$this->addExpression('sms_opp_count')->set("'ToDo'");
		$this->addExpression('social_count')->set("'ToDo'");
		$this->addExpression('social_opp_count')->set("'ToDo'");
		$this->addExpression('tele_count')->set("'ToDo'");
		$this->addExpression('tele_opp_count')->set("'ToDo'");
		$this->addExpression('personal_opp_count')->set("'ToDo'");
	}
}