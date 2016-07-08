<?php

namespace xepan\marketing;

class Model_TeleCommunication extends \xepan\communication\Model_Communication{
	function init(){
		parent::init();

		$this->addCondition('communication_type','TeleMarketing');
		$this->getElement('direction')->defaultValue('Out');
	}
}