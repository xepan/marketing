<?php

namespace xepan\marketing;

class Model_MarketingCategory extends \xepan\hr\Model_Document{

	public $status=['All'];
	public $actions=['All'=>['view','edit','delete','merge_category','delete_all_lead']];

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
		$marketing_category_m = $this->add('xepan\marketing\Model_MarketingCategory')
									 ->addCondition('id','<>',$this->id);
		$form = $p->add('Form');
		$condition_field = $form->addField('xepan\base\DropDown','condition')->setValueList(['and'=>'Common (AND)','or'=>'Any (OR)']);
		$category_field = $form->addField('xepan\base\DropDown','category','');
	    $category_field->setModel($marketing_category_m);
		$category_field->setEmptyText('Please select categories to merge');
		$category_field->setAttr(['multiple'=>'multiple']);
		$merge_field = $form->addField('checkbox','associate_orphans');
		$form->addSubmit('Merge');
		
		if($form->isSubmitted()){
			$lead_cat = $this->add('xepan\marketing\Model_Lead_Category_Association');
			$category_array = explode(",", $form['category']);
			$lead_cat->addCondition('marketing_category_id',$category_array);
			
			if($form['condition'] === 'and'){
				$lead_cat->_dsql()->having($lead_cat->dsql()->expr('(COUNT(DISTINCT([0])) = [1])',[$lead_cat->getElement('marketing_category_id'),count($category_array)]));
			}
			
			$lead_cat->_dsql()->group('lead_id');
			
			foreach ($lead_cat as $l) {				
				$lead_m = $this->add('xepan\marketing\Model_Lead');				
				$lead_m->tryLoad($l['lead_id']);
				if($lead_m->loaded())
					$lead_m->associateCategory($this->id);
			}

			if($form['associate_orphans']){
				$this->associate_orphans($this->id);	
			}

			$this->app->employee
				->addActivity("Category '".$this['name']."' and ".$form['category']."' merged'", $this->id, null,null,null,"xepan_marketing_marketingcategory")
				->notifyWhoCan('view,edit,delete','All');
			return $p->js()->univ()->closeDialog();
		}
	}

	function associate_orphans($cat_id){
		$orphan_lead_m = $this->add('xepan\marketing\Model_Lead');
		
		$orphan_lead_m->addExpression('has_category')->set(function($m,$q){
			$assoc = $this->add('xepan\marketing\Model_Lead_Category_Association');
			$assoc->addCondition('lead_id',$m->getElement('id'));
			return $assoc->count();	
		})->type('boolean');

		$orphan_lead_m->addCondition('has_category',false);

		foreach ($orphan_lead_m as $orphan) {
			$orphan->associateCategory($cat_id);	
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

	function delete_all_lead(){
		$lead_cat = $this->add('xepan\marketing\Model_Lead_Category_Association');
		$lead_cat->addCondition('marketing_category_id',$this->id);
		// throw new \Exception($lead_cat->count(), 1);
		
		// $lead_cat->_dsql()->group('lead_id');
		// try{
			// $this->api->db->beginTransaction();
			$lead_array=[];
			foreach ($lead_cat as $l) {
				$lead_array[]=$l['lead_id'];
			}

			$lead_communication = $this->add('xepan\communication\Model_Communication')
					->addCondition([['from_id',$lead_array],['to_id',$lead_array],['created_by_id',$lead_array]]);			
			$attachment = $this->add('xepan\communication\Model_Communication_Attachment');
			
			$attachment->addExpression('communication_created_by_id')->set($attachment->refSQL('communication_id')->fieldQuery('created_by_id'));
			$attachment->addExpression('communication_from_id')->set($attachment->refSQL('communication_id')->fieldQuery('from_id'));
			$attachment->addExpression('communication_to_id')->set($attachment->refSQL('communication_id')->fieldQuery('to_id'));
			$attachment->addCondition([['communication_created_by_id',$lead_array],['communication_from_id',$lead_array],['communication_to_id',$lead_array]]);			
			$attachment->deleteAll();

			// delete communication
			$lead_communication->deleteAll();

			$this->add('xepan\base\Model_Contact_Email')
					->addCondition('contact_id',$lead_array)
					->deleteAll();
			$this->add('xepan\base\Model_Contact_Phone')
					->addCondition('contact_id',$lead_array)
					->deleteAll();
			$this->add('xepan\base\Model_Contact_Relation')
					->addCondition('contact_id',$lead_array)
					->deleteAll();
			$this->add('xepan\base\Model_Contact_IM')
					->addCondition('contact_id',$lead_array)
					->deleteAll();
			$this->add('xepan\base\Model_Contact_Event')
					->addCondition('contact_id',$lead_array)
					->deleteAll();		

			$lead_m = $this->add('xepan\marketing\Model_Lead')
						->addCondition('id',$lead_array)
						->deleteAll();

			$lead_cat->deleteAll();			

			// echo "<pre>";
			// print_r($lead_array);
			// echo "</pre>";
		
		// 	$this->api->db->commit();
		// }catch(\Exception_StopInit $e){

		// }catch(\Exception $e){
		// 	$this->api->db->rollback();
		// 	throw $e;
		// }
	}
	
}