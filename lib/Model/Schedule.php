<?php

namespace xepan\marketing;

class Model_Schedule extends \xepan\base\Model_Table{
	public $table = "schedule";
	function init(){
		parent::init();

		$this->hasOne('xepan/marketing/Campaign','campaign_id');
		$this->hasOne('xepan/marketing/Content','document_id');
		$this->addField('date')->type('datetime');
		$this->addField('client_event_id');
		$this->addField('day')->type('Number');

		$this->addHook('beforeSave',$this);
		$this->addHook('beforeDelete',$this);

	}
}