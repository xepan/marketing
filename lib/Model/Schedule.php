<?php

namespace xepan\marketing;

class Model_Schedule extends \xepan\base\Model_Table{
	public $table = "schedule";
	function init(){
		parent::init();

		$this->hasOne('xepan/marketing/Campaign','campaign_id');
		$this->addField('date')->type('datetime');
		$this->addField('day');

		$this->hasMany('xepan/marketing/Campaign','Schedule_id');
	}
}