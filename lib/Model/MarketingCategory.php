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

	function page_delete_all_lead($page){

		$form = $page->add('Form');
		$type_field = $form->addField('DropDown','contact')
				->setValueList([
					'All'=>'All',
					'Lead'=>"Lead",
					'Customer'=>'Customer',
					'Supplier'=>'Supplier',
					'Affiliate'=>'Affiliate'
				]);
		$form->addField('checkbox','remove_related_document')->set(1);
		$form->addField('Number','lead_having_score_less_then')->set(0);
		$form->addSubmit('delete contact now');
		if($form->isSubmitted()){

			$delete_record = $this->delete_all_lead($form['contact'],$form['lead_having_score_less_then']);

			$msg = "Category Record ";
			foreach ($delete_record as $key => $value) {
				$msg .= $key."= ".$value.",";
			}
			$msg = trim($msg,",");

			$this->app->employee
				->addActivity( $msg, $this->id, null,null,null,"xepan_marketing_marketingcategory")
				->notifyWhoCan('view,edit,delete,delete_all_lead','All');
			return $page->js()->univ()->closeDialog();
		}

	}

	function delete_all_lead($contact_type = "All",$lead_score=0){
		$delete_record = [];
		ini_set('max_execution_time', 0);
		
		// $dsql= $this->db->dsql();
		// $dsql->sql_templates['delete']='delete [table] from  [table_noalias] [join] [where]';

		$lead_cat = $this->add('xepan\marketing\Model_Lead_Category_Association');
		$lead_cat->addCondition('marketing_category_id',$this->id);
		$lead_cat->addExpression('lead_type')->set($lead_cat->refSQL('lead_id')->fieldQuery('type'));

		if($contact_type != 'All')
			$lead_cat->addCondition('lead_type',$contact_type);

		$delete_record['category_association'] = $lead_cat->count()->getOne();

		$lead_array=[];
		foreach ($lead_cat as $l) {
			$lead_array[]=$l['lead_id'];
		}

		// delete communication
		$lead_communication = $this->add('xepan\communication\Model_Communication')
				->addCondition([['from_id',$lead_array],['to_id',$lead_array],['created_by_id',$lead_array]]);
		$delete_record['communication'] = $lead_communication->count()->getOne();
		$lead_communication->deleteAll();


		$attachment = $this->add('xepan\communication\Model_Communication_Attachment');
		$attachment->addExpression('communication_created_by_id')->set($attachment->refSQL('communication_id')->fieldQuery('created_by_id'));
		$attachment->addExpression('communication_from_id')->set($attachment->refSQL('communication_id')->fieldQuery('from_id'));
		$attachment->addExpression('communication_to_id')->set($attachment->refSQL('communication_id')->fieldQuery('to_id'));
		$attachment->addCondition([['communication_created_by_id',$lead_array],['communication_from_id',$lead_array],['communication_to_id',$lead_array]]);
		$delete_record['communication_attachment'] = $attachment->count()->getOne();
		$attachment->deleteAll();

		$email_model = $this->add('xepan\base\Model_Contact_Email')
					->addCondition('contact_id',$lead_array);
		$delete_record['contact_email'] = $email_model->count()->getOne();
		$email_model->deleteAll();

		$phone_model = $this->add('xepan\base\Model_Contact_Phone')
						->addCondition('contact_id',$lead_array);
		$delete_record['contact_phone'] = $phone_model->count()->getOne();
		$phone_model->deleteAll();

		$relation_model = $this->add('xepan\base\Model_Contact_Relation')
						->addCondition('contact_id',$lead_array);
		$delete_record['contact_relation'] = $relation_model->count()->getOne();
		$relation_model->deleteAll();

		$im_model = $this->add('xepan\base\Model_Contact_IM')
					->addCondition('contact_id',$lead_array);
		$delete_record['contact_im'] = $im_model->count()->getOne();
		$im_model->deleteAll();

		$event_model = $this->add('xepan\base\Model_Contact_Event')
						->addCondition('contact_id',$lead_array);
		$delete_record['contact_event'] = $event_model->count()->getOne();
		$event_model->deleteAll();	

		// Remove related Document like SaleOrder, SaleInvoice, Quotation etc.
		switch ($contact_type) {
			case 'All':
				$document_type = ['xepan\commerce\Model_SalesInvoice','xepan\commerce\Model_SalesOrder','xepan\commerce\Model_Quotation','xepan\commerce\Model_PurchaseInvoice','xepan\commerce\Model_PurchaseOrder'];
				$contact_model_class = 'xepan\marketing\Model_Lead';
			break;
			case 'Customer':
				$document_type = ['xepan\commerce\Model_SalesInvoice','xepan\commerce\Model_SalesOrder','xepan\commerce\Model_Quotation'];
				$contact_model_class = 'xepan\commerce\Model_Customer';
			break;
			case 'Supplier':
				$document_type = ['xepan\commerce\Model_PurchaseInvoice','xepan\commerce\Model_PurchaseOrder'];
				$contact_model_class = 'xepan\commerce\Model_Supplier';
			break;
		}

		if(isset($document_type)){
			foreach ($document_type as $key => $model_class) {
				$d_m = $this->add($model_class)
						->addCondition([['contact_id',$lead_array],['created_by_id',$lead_array]])
						;
				$document_ids = [];
				foreach ($d_m as $d) {
					$document_ids[] = $d->id;
				}

				$delete_record[$model_class] = $d_m->count()->getOne();
				// remove attachment
				if(count($document_ids)){
					$a = $this->add('xepan\base\Model_Document_Attachment')
						->addCondition('document_id',$document_ids);

					$delete_record[$model_class."_attachment"] = $a->count()->getOne();
					$a->delete();
				}
				$d_m->each(function($d){
					$d->delete();
				});
			}
		}

		// remove all leads
		$contact_model = $this->add($contact_model_class)
				->addCondition('id',$lead_array);
		if($lead_score)
			$contact_model->addCondition('score','<',$lead_score);

		$delete_record["Contact_".$contact_type] = $contact_model->count()->getOne();
		$contact_model->deleteAll();

		// remove all cat association
		$lead_cat->deleteAll();

		return $delete_record;
	}


}