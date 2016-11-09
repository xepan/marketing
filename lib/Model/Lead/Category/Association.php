<?php

namespace xepan\marketing;

class Model_Lead_Category_Association extends \xepan\base\Model_Table{
	public $table = "lead_category_association";

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
		
		
		$this->hasOne('xepan\marketing\Lead','lead_id');
		$this->hasOne('xepan\marketing\MarketingCategory','marketing_category_id');
		$this->addField('created_at')->type('datetime')->defaultValue($this->app->now);
		// $this->addExpression('name')->set("'hello'");
	}	
}
