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
		$lead_j->hasOne('xepan\marketing\MarketingCategory','marketing_category_id');
		$lead_j->addField('source');
		$lead_j->hasMany('xepan\marketing\Opportunity','lead_id',null,'Opportunity');
		
		$this->addCondition('type','Lead');

	}
} 