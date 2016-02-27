<?php

namespace xepan\marketing;

class Model_Sms extends \xepan\marketing\Model_Content{

	function init(){
		parent::init();
		

		$this->getElement('status')->defaultValue('Draft');
		$this->addCondition('type','Sms');

	}
}
