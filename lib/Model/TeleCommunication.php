<?php

namespace xepan\marketing;

class Model_TeleCommunication extends \xepan\communication\Model_Communication_TeleMarketing{
	public $acl=true;
	public $actions = ['*'=>['view','edit','delete']];
	function init(){
		parent::init();


		$this->addCondition('communication_type','TeleMarketing');
		$this->getElement('direction')->defaultValue('Out');
		$this->addCondition('type','TeleCommunication');
	}
}