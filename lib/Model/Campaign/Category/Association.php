<?php

namespace xepan\marketing;

class Model_Campaign_Category_Association extends \xepan\base\Model_Table{
	public $table = "campaign_category_association";

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
		
		
		$this->hasOne('xepan\marketing\MarketingCategory','marketing_category_id');
		$this->hasOne('xepan\marketing\Campaign','campaign_id');
		$this->addField('created_at')->type('datetime')->defaultValue($this->app->now);
	}	
}
