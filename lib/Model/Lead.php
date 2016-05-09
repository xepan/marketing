<?php

namespace xepan\marketing;  

class Model_Lead extends \xepan\base\Model_Contact{

	public $status = ['Active','InActive'];
	public $actions = [
					'Active'=>['view','edit','delete','deactivate','communication'],
					'InActive'=>['view','edit','delete','activate','communication']
					];

	function init(){
		parent::init();
		
		$this->getElement('created_by_id')->defaultValue($this->app->employee->id);
		
		$lead_j = $this->join('lead.contact_id');
		$lead_j->addField('source');
		// $lead_count=$lead_j->addExpression('count_leads')->set(function($m){
		// 	return $m->refSQL('xepan\marketing\Model_Opportunity')->count();
		// });

		$this->addExpression('open_count')->set(function($m,$q){
			return $this->add('xepan\marketing\Model_Opportunity',['table_alias'=>'open_count'])
						->addCondition('lead_id',$q->getField('id'))
						->addCondition('status','Open')
						->count();
		});

		$this->addExpression('converted_count')->set(function($m,$q){
			return $this->add('xepan\marketing\Model_Opportunity',['table_alias'=>'converted_count'])
						->addCondition('lead_id',$q->getField('id'))
						->addCondition('status','Converted')
						->count();
		});

		$this->addExpression('rejected_count')->set(function($m,$q){
			return $this->add('xepan\marketing\Model_Opportunity',['table_alias'=>'rejected_count'])
						->addCondition('lead_id',$q->getField('id'))
						->addCondition('status','Rejected')
						->count();
		});

		$lead_j->hasMany('xepan\marketing\Opportunity','lead_id',null,'Opportunities');
		$lead_j->hasMany('xepan\marketing\Lead_Category_Association','lead_id');
		
		$this->addCondition('type','Lead');
		$this->getElement('status')->defaultValue('Active');
		$this->addHook('beforeSave',$this);
		$this->addHook('beforeDelete',[$this,'checkExistingOpportunities']);
		$this->addHook('beforeDelete',[$this,'checkExistingCategoryAssociation']);

	}

	function activate(){
		$this['status']='Active';
		$this->app->employee
            ->addActivity("Lead now active", null/* Related Document ID*/, $this->id /*Related Contact ID*/)
            ->notifyWhoCan('activate','InActive',$this);
		$this->save();
	}

	//deactivate Lead
	function deactivate(){
		$this['status']='InActive';
		$this->app->employee
            ->addActivity("Lead has deactivated", null/* Related Document ID*/, $this->id /*Related Contact ID*/)
            ->notifyWhoCan('deactivate','Active',$this);
		$this->save();
	}

	function rule_abcd($a){

	}

	//activate Lead

	function beforeSave($m){}

	function checkExistingOpportunities($m){
		$opp_count = $this->ref('Opportunities')->count()->getOne();
		if($opp_count)
			throw $this->exception('Cannot Delete,first delete Opportunitie`s ');	
	}

	function checkExistingCategoryAssociation($m){
		$cat_ass_count = $this->ref('xepan\marketing\Lead_Category_Association')->count()->getOne();
		if($cat_ass_count)
			throw $this->exception('Cannot Delete,first delete Category Association`s ');	
	}

	function getAssociatedCategories(){

		$associated_categories = $this->ref('xepan\marketing\Lead_Category_Association')
								->_dsql()->del('fields')->field('marketing_category_id')->getAll();
		return iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($associated_categories)),false);
	}

	function removeAssociateCategory(){
		$this->ref('xepan\marketing\Lead_Category_Association')->deleteAll();
	}

	function associateCategory($category){
		return $this->add('xepan\marketing\Model_Lead_Category_Association')
						->addCondition('lead_id',$this->id)
		     			->addCondition('marketing_category_id',$category)
			 			->tryLoadAny()	
			 			->save();
	}

} 
