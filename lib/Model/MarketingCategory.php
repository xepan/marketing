<?php

namespace xepan\marketing;

class Model_MarketingCategory extends \xepan\base\Model_Document{

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
		
		$cat_j = $this->join('marketingcategory.document_id');
		$cat_j->addField('name');
		
		$cat_j->hasMany('xepan\marketing\CampaignCategory','marketingcategory_id');
		$cat_j->hasMany('xepan\marketing\Lead','marketing_category_id');

		$this->addExpression('leads_count')->set($this->refSql('xepan\marketing\Lead')->count());
		$this->addCondition('type','MarketingCategory');

	}
}
