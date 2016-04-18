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
		
		$cat_j->hasMany('xepan\marketing\Lead_Category_Association','marketing_category_id');
		$cat_j->hasMany('xepan\marketing\Campaign_Category_Association','marketing_category_id');

		$this->addExpression('leads_count')->set($this->refSQL('xepan\marketing\Lead_Category_Association')->count());
		$this->addCondition('type','MarketingCategory');

		$this->addHook('beforeSave',$this);
		$this->addHook('beforeDelete',[$this,'checkExistingLeadCategoryAssociation']);
		$this->addHook('beforeDelete',[$this,'checkExistingCampaignCategoryAssociation']);

	}

	function beforeSave($m){}

	function checkExistingLeadCategoryAssociation($m){
		$lead__cat_count = $m->ref('xepan\marketing\Lead_Category_Association')->count()->getOne();

		if($lead__cat_count)
			throw $this->exception('Cannot Delete,first delete Campaign`s Category Association ');	
	}
		

	function checkExistingCampaignCategoryAssociation($m){
		$campaign_catasso_count = $m->ref('xepan\marketing\Campaign_Category_Association')->count()->getOne();
		
		if($campaign_catasso_count)
			throw $this->exception('Cannot Delete,first delete Lead`s Category Association');	
	}

}