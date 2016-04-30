<?php

namespace xepan\marketing;

class page_leaddetails extends \xepan\base\Page {
	public $title ='Lead Details';
	public $breadcrumb=['Home'=>'index','Lead'=>'xepan_marketing_lead','Details'=>'#'];


	function init(){
		parent::init();

		$action = $this->api->stickyGET('action')?:'view';
		$lead= $this->add('xepan\marketing\Model_Lead')->tryLoadBy('id',$this->api->stickyGET('contact_id'));
		$lead_view = $this->add('xepan\base\View_Contact',['acl'=>'xepan\marketing\Model_Lead'],'contact_view');
		$lead_view->setModel($lead);

		$detail = $this->add('xepan\hr\View_Document',['action'=> $action,'id_field_on_reload'=>'contact_id'],'details',['view/details']);
		$detail->setModel($lead,['source','marketing_category','communication','opportunities'],['source','marketing_category_id','communication','opportunities']);

			
		if($lead->loaded()){
			$opportunities_tab = $this->add('xepan\hr\View_Document',['action'=> $action,'id_field_on_reload'=>'contact_id'],'opportunity',['view/opp']);
			$o = $opportunities_tab->addMany('opportunity',null,'opportunity',['grid/addopportunity-grid']);
			$o->setModel($lead->ref('Opportunities'));
			
			/*
			*	
			*	Lead <=> Category association form
			*	
			*/
			
			$model_assoc_category = $this->add('xepan\marketing\Model_MarketingCategory');

			if($action=='view'){
				$base= $detail;
				
				$asso = $lead->ref('xepan\marketing\Lead_Category_Association');

				$category_assoc_grid = $base->add('xepan\base\Grid',['show_header'=>false],'marketing_category');
				$category_assoc_grid->setModel($asso,['marketing_category'])
							        ->_dsql()->group('marketing_category_id');
			}

			else{

				$base = $detail->form->layout;

				$cat_ass_field = $base->addField('hidden','ass_cat')->set(json_encode($lead->getAssociatedCategories()));

				$base->addField('hidden','contact_id')->set($_GET['contact_id']);

				$category_assoc_grid = $base->add('xepan\base\Grid',['show_header'=>false],'marketing_category');
				$category_assoc_grid->setModel($model_assoc_category,['name'],['name']);
				$category_assoc_grid->addSelectable($cat_ass_field);

				$detail->form->onSubmit(function($frm){

				$lead_model = $this->add('xepan\marketing\Model_lead')->load($_GET['contact_id']);	
				$lead_model->removeAssociateCategory();

				$selected_categories = json_decode($frm['ass_cat'],true);

				foreach ($selected_categories as $cat) {
					$lead_model->associateCategory($cat);
				}

				$frm->save();
				$frm->js(null,$this->js()->univ()->successMessage('Lead associated with categories'))->reload()->execute();	
				});
			
			}

		}
			/*
			*	
			*	Lead <=> Category association form
			*	
			*/
	}

	function defaultTemplate(){
		return ['page/leadprofile'];
	}
}
