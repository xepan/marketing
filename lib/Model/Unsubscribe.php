<?php

namespace xepan\marketing;

class Model_Unsubscribe extends \xepan\base\Model_Table{
	public $table = "unsubscribe";
	public $acl = false;

	function init(){
		parent::init();
		
		$this->hasOne('xepan\base\Contact','contact_id');
		$this->hasOne('xepan\base\Document','document_id');

		$this->addField('reason')->type('text');
		$this->addField('created_at')->type('datetime');
	}
}