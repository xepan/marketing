<?php

namespace xepan\marketing;

class Model_MarketingCategory extends \xepan\hr\Model_Document{

	public $status=['All'];
	public $actions=['All'=>['view','edit','delete','merge_category']];

	function init(){
		parent::init();
		
		$cat_j = $this->join('marketingcategory.document_id');
		$cat_j->addField('name')->sortable(true);
		$cat_j->addField('system')->type('boolean');

		$cat_j->hasMany('xepan\marketing\Lead_Category_Association','marketing_category_id');
		$cat_j->hasMany('xepan\marketing\Campaign_Category_Association','marketing_category_id');

		// $this->addExpression('leads_count')->set($this->refSQL('xepan\marketing\Lead_Category_Association')->count());
		
		$this->addCondition('type','MarketingCategory');
		$this->addCondition('status','All');
		
		$this->getElement('created_by_id')->defaultValue($this->app->employee->id);

		$this->addExpression('leads_count')->set(function($m){
			$association = $this->add('xepan\marketing\Model_Lead_Category_Association');
			$association->addCondition('marketing_category_id',$m->getElement('id'));
			return $association->count();
			// return $m->refSQL('xepan\marketing\Lead_Category_Association')->count();
		})->sortable(true);

		$this->addHook('beforeDelete',[$this,'checkExistingLeadCategoryAssociation']);
		$this->addHook('beforeDelete',[$this,'checkExistingCampaignCategoryAssociation']);
		$this->addHook('beforeSave',[$this,'updateSearchString']);
	}

	function page_merge_category($p){
		$p->add('View')->set('Add leads in this catagory which are common in selected categories');
		$marketing_category_m = $this->add('xepan\marketing\Model_MarketingCategory')
									 ->addCondition('id','<>',$this->id);
		$form = $p->add('Form');
		$category_field = $form->addField('xepan\base\DropDown','category','');
	    $category_field->setModel($marketing_category_m);
		$category_field->setEmptyText('Please select categories to merge');
		$category_field->setAttr(['multiple'=>'multiple']);
		$form->addSubmit('Merge');
		
		if($form->isSubmitted()){
			$lead_cat = $this->add('xepan\marketing\Model_Lead_Category_Association');
			
			$category_array = explode(",", $form['category']);
			if(count($category_array) == 1)
				$form->displayError('category','Please select two or more categories');
			
			$lead_cat->addCondition('marketing_category_id',$category_array);
			$lead_cat->_dsql()->having($lead_cat->dsql()->expr('(COUNT(DISTINCT([0])) = [1])',[$lead_cat->getElement('marketing_category_id'),count($category_array)]));
			$lead_cat->_dsql()->group('lead_id');

			foreach ($lead_cat as $l) {
				$lead_m = $this->add('xepan\marketing\Model_Lead');				
				$lead_m->load($l['lead_id']);

				$lead_m->associateCategory($this->id);
			}

			$this->app->employee
				->addActivity("Category '".$this['name']."' and ".$form['category']."' merged'", $this->id, null,null,null,"xepan_marketing_marketingcategory")
				->notifyWhoCan('view,edit,delete','All');
			return $p->js()->univ()->closeDialog();
		}
	}


	function updateSearchString($m){

		$search_string = ' ';
		$search_string .=" ". $this['name'];
		$search_string .=" ". $this['type'];
		$search_string .=" ". $this['status'];

		$this['search_string'] = $search_string;
	}

	function checkExistingLeadCategoryAssociation($m){
		$lead__cat_count = $m->ref('xepan\marketing\Lead_Category_Association')->count()->getOne();
		
		if($lead__cat_count){
			$model = $this->add('xepan\marketing\Model_Lead_Category_Association');
			$model->addCondition('marketing_category_id',$this->id);

			foreach ($model as $m){
				$m->delete();		
			}	
		}
	}
		

	function checkExistingCampaignCategoryAssociation($m){
		$campaign_catasso_count = $m->ref('xepan\marketing\Campaign_Category_Association')->count()->getOne();
	
		if($campaign_catasso_count){
			$model = $this->add('xepan\marketing\Model_Campaign_Category_Association');
			$model->addCondition('marketing_category_id',$this->id);

			foreach ($model as $m){
				$m->delete();		
			}
		}
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