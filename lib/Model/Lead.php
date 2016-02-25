<?php

namespace xepan\marketing;  

class Model_Lead extends \xepan\base\Model_Contact{

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

	function init(){
		parent::init();
		
		$lead_j = $this->join('lead.contact_id');
		$lead_j->hasOne('xepan\marketing\LeadCategory','category_contact_id');
		$lead_j->addField('source');
		$lead_j->hasMany('xepan\marketing\Opportunity','lead_id');
		
		$this->addCondition('type','Lead');

	}
} 