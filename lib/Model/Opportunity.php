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
		$opp_j->addField('title')->sortable(true);
		$opp_j->addField('description')->type('text');

		$this->addExpression('duration')->set('"TODO"');
		$this->addExpression('source')->set($this->refSql('lead_id')->fieldQuery('source'));
		$this->getElement('status')->defaultValue('Open');
		$this->addCondition('type','Opportunity');

		$this->addHook('beforeSave',[$this,'updateSearchString']);
	}

	function updateSearchString($m){

		$search_string = ' ';
		$search_string .=" ". $this['title'];
		$search_string .=" ". $this['description'];
		$search_string .=" ". $this['duration'];
		$search_string .=" ". $this['source'];

		$this['search_string'] = $search_string;
	}

	function convert(){
		$this['status']='Converted';

		$this->app->employee
			->addActivity("Converted Opportunity", $this->id, $this['lead_id'])
			->notifyWhoCan('reject,open','Converted');
		$this->saveAndUnload();	
		// $this->saveAs('xepan\marketing\Model_Opportunity');
	}

	function reject(){
		$this['status']='Rejected';
		$this->app->employee
			->addActivity("Rejected Opportunity", $this->id, $this['lead_id'])
			->notifyWhoCan('convert,open','Rejected');
		$this->saveAndUnload();
		// $this->saveAs('xepan\marketing\Model_Opportunity');
	}

	function open(){
		$this['status']='Open';
		$this->app->employee
			->addActivity("Opened Opportunity", $this->id, $this['lead_id'])
			->notifyWhoCan('reject,convert','Open');
		$this->saveAndUnload();
		// $this->saveAs('xepan\marketing\Model_Opportunity');
	}
} 