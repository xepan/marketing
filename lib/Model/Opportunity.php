<?php

namespace xepan\marketing;  

class Model_Opportunity extends \xepan\hr\Model_Document{

	public $status=[
		'Open',
		'Converted',
		'Rejected'
	];
	public $actions=[
		'Open'=>['view','edit','delete','convert','reject'],
		'Converted'=>['view','edit','delete','open','reject'],
		'Rejected'=>['view','edit','delete','open','convert']
	];

	function init(){
		parent::init();

		$opp_j=$this->join('opportunity.document_id');
		$opp_j->hasOne('xepan\marketing\Lead','lead_id');
		$opp_j->addField('title');
		$opp_j->addField('description')->type('text');

		$this->addExpression('duration')->set('"TODO"');
		$this->addExpression('source')->set($this->refSql('lead_id')->fieldQuery('source'));
		$this->getElement('status')->defaultValue('Open');
		$this->addCondition('type','Opportunity');
	}

	function convert(){
		$this['status']='Converted';
		$this->saveAndUnload();
	}

	function reject(){
		$this['status']='Rejected';
		$this->saveAndUnload();
	}

	function open(){
		$this['status']='Open';
		$this->saveAndUnload();
	}
} 