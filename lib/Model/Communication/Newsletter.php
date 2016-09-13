<?php

namespace xepan\marketing;

class Model_Communication_Newsletter extends \xepan\communication\Model_Communication_Abstract_Email{
	public $status=['Draft','Outbox','Sent','Trashed'];
	function init(){
		parent::init();
		$this->getElement('status')->defaultValue('Draft');
		$this->addCondition('communication_type','Newsletter');	
		$this->addCondition('direction','Out');	
	}
}
