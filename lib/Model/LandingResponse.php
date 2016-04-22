<?php

namespace xepan\marketing;

class Model_LandingResponse extends \xepan\base\Model_Table{
	public $table = "landingresponse";
	public $status=[
	];
	public $actions=[
		'*'=>[
			'add',
			'view',
			'edit',
			'delete'
		]
	];
	public $acl=false; 

	function init(){
		parent::init();

		$this->hasOne('xepan\marketing\Campaign','campaign_id');
		$this->hasOne('xepan\marketing\Lead','lead_id');
		$this->hasOne('xepan\marketing\Opportunity','opportunity_id');
		$this->addField('date')->type('datetime');
		$this->addField('action');
		$this->addField('type')->hint('call/newsletter/sitevisit/email....');
		$this->addField('ip');
		$this->addField('latitude');
		$this->addField('longitude');
	}
}