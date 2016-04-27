<?php

namespace xepan\marketing;

class Model_MarketingCategory extends \xepan\base\Model_Document{

	public $status=[];
	public $actions=[
		'All'=>[
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

		// $this->addExpression('leads_count')->set($this->refSQL('xepan\marketing\Lead_Category_Association')->count());
		
		$this->addCondition('type','MarketingCategory');
		
		$this->getElement('created_by_id')->defaultValue($this->app->employee->id);

		$this->addExpression('leads_count')->set(function($m){
			return $m->refSQL('xepan\marketing\Lead_Category_Association')->count();
		});

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

	function getAssociatedLeads(){

		$associated_leads = $this->ref('xepan\marketing\Lead_Category_Association')
								->_dsql()->del('fields')->field('lead_id')->getAll();
		return iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($associated_leads)),false);
	}

	function getAssociatedCampaigns(){

		$associated_campaigns = $this->ref('xepan\marketing\Campaign_Category_Association')
								->_dsql()->del('fields')->field('campaign_id')->getAll();
		return iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($associated_campaigns)),false);
	}

	function removeAssociatedLeads(){
		$this->ref('xepan\marketing\Lead_Category_Association')
								->deleteAll();
	}

	function removeAssociatedCampaigns(){
		$this->ref('xepan\marketing\Campaign_Category_Association')
								->deleteAll();
	}
	
}