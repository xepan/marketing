<?php

namespace xepan\marketing;  

class Model_Opportunity extends \xepan\hr\Model_Document{

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

		$opp_j=$this->join('opportunity.document_id');
		$opp_j->hasOne('xepan\marketing\Lead','lead_id');
		$opp_j->addField('title');
		$opp_j->addField('description')->type('text');

		$this->addExpression('duration')->set('"TODO"');
		$this->addExpression('source')->set('"source_TODO"');
		$this->getElement('status')->defaultValue('Active');
		$this->addCondition('type','Opportunity');
	}
} 