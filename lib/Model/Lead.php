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
		$this->addHook('beforeDelete',[$this,'checkExistingOpportunities']);
		$this->addHook('beforeDelete',[$this,'checkExistingCategoryAssociation']);
		$this->addHook('beforeSave',[$this,'updateSearchString']);

	}

	function updateSearchString($m){
		$search_string = ' ';
		$search_string .=" ". $this['name'];
		$search_string .=" ". str_replace("<br/>", " ", $this['contacts_str']);
		$search_string .=" ". str_replace("<br/>", " ", $this['emails_str']);
		$search_string .=" ". $this['source'];
		$search_string .=" ". $this['type'];
		$search_string .=" ". $this['city'];
		$search_string .=" ". $this['state'];
		$search_string .=" ". $this['pin_code'];
		$search_string .=" ". $this['organization'];
		$search_string .=" ". $this['post'];
		$search_string .=" ". $this['website'];

		$this['search_string'] = $search_string;
	}

	function quickSearch($app,$search_string,$view){
		$this->addExpression('Relevance')->set('MATCH(search_string) AGAINST ("'.$search_string.'" IN NATURAL LANGUAGE MODE)');
		$this->addCondition('Relevance','>',0);
 		$this->setOrder('Relevance','Desc');
 		if($this->count()->getOne()){
 			$lc = $view->add('Completelister',null,null,['grid/quicksearch-marketing-grid']);
 			$lc->setModel($this);
 		}

 		$opportunity = $this->add('xepan\marketing\Model_Opportunity');
		$opportunity->addExpression('Relevance')->set('MATCH(search_string) AGAINST ("'.$search_string.'" IN NATURAL LANGUAGE MODE)');
		$opportunity->addCondition('Relevance','>',0);
 		$opportunity->setOrder('Relevance','Desc'); 		
 		if($opportunity->count()->getOne()){
 			$oc = $view->add('Completelister',null,null,['grid/quicksearch-marketing-grid']);
 			$oc->setModel($opportunity);	
 		}

 		$category = $this->add('xepan\marketing\Model_MarketingCategory');
		$category->addExpression('Relevance')->set('MATCH(search_string) AGAINST ("'.$search_string.'" IN NATURAL LANGUAGE MODE)');
		$category->addCondition('Relevance','>',0);
 		$category->setOrder('Relevance','Desc');
 		if($category->count()->getOne()){
 			$cc = $view->add('Completelister',null,null,['grid/quicksearch-marketing-grid']);
 			$cc->setModel($category);
 		}

 		$content = $this->add('xepan\marketing\Model_Content');
		$content->addExpression('Relevance')->set('MATCH(search_string) AGAINST ("'.$search_string.'" IN NATURAL LANGUAGE MODE)');
		$content->addCondition('Relevance','>',0);
 		$content->setOrder('Relevance','Desc');
 		if($content->count()->getOne()){
 			$c = $view->add('Completelister',null,null,['grid/quicksearch-marketing-grid']);
 			$c->setModel($content); 	
 		}

 		$campaign = $this->add('xepan\marketing\Model_Campaign');
		$campaign->addExpression('Relevance')->set('MATCH(search_string) AGAINST ("'.$search_string.'" IN NATURAL LANGUAGE MODE)');
		$campaign->addCondition('Relevance','>',0);
 		$campaign->setOrder('Relevance','Desc');
 		if($campaign->count()->getOne()){
 			$c = $view->add('Completelister',null,null,['grid/quicksearch-marketing-grid']);
 			$c->setModel($campaign); 
 		}
 		
 		$tele = $this->add('xepan\marketing\Model_TeleCommunication');
		$tele->addExpression('Relevance')->set('MATCH(title,description) AGAINST ("'.$search_string.'" IN NATURAL LANGUAGE MODE)');
		$tele->addCondition('Relevance','>',0);
 		$tele->setOrder('Relevance','Desc');
 		if($tele->count()->getOne()){
 			$c = $view->add('Completelister',null,null,['grid/quicksearch-marketing-grid']);
 			$c->setModel($tele); 

 		}
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
