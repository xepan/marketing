<?php

namespace xepan\marketing;

class page_leaddetails extends \Page {
	public $title ='Lead Details';

	function init(){
		parent::init();

		$action = $this->api->stickyGET('action')?:'view';
		$lead= $this->add('xepan\marketing\Model_Lead')->tryLoadBy('id',$this->api->stickyGET('contact_id'));
		$lead_view = $this->add('xepan\base\View_Contact',null,'contact_view');
		$lead_view->setModel($lead);


		

		if($lead->loaded()){

			$detail = $this->add('xepan\hr\View_Document',['action'=> $action,'id_field_on_reload'=>'contact_id'],'details',['view/details']);

	

			// $submit_btn = $form->addButton('Update');

			// $js=[
			// 	$form->js()->submit()
			// ];

			// $submit_btn->js('click',$js);

		}

			$detail->setModel($lead,['source','marketing_category','communication','opportunities'],['source','marketing_category_id','communication','opportunities']);
			$opportunities_tab = $this->add('xepan\hr\View_Document',['action'=> $action,'id_field_on_reload'=>'contact_id'],'opportunity',['view/opp']);
			$o = $opportunities_tab->addMany('opportunity',null,'opportunity',['grid/addopportunity-grid']);
			$o->setModel($lead->ref('Opportunity'));
			if($action=='view'){
				$base= $detail;
			}
			else{

				$base=  $detail->form->layout;

				$cat_ass_field = $base->addField('line','ass_cat')->set(json_encode($lead->getAssociatedCategories()));

				$lead_id = $base->addField('line','contact_id')->set($_GET['contact_id']);

				$model_assoc_category = $this->add('xepan\marketing\Model_MarketingCategory');

				$category_assoc_grid = $base->add('xepan\base\Grid',null,'marketing_category');
				$category_assoc_grid->setModel($model_assoc_category,['name'],['name']);
				$category_assoc_grid->addSelectable($cat_ass_field);

				$detail->form->onSubmit(function($frm){

				$selected_categories = array();
				$selected_categories = json_decode($frm['ass_cat'],true);

				$model_asso = $this->add('xepan\marketing\Model_Lead_Category_Association');
				
				// $previous_cat_array = $lead->getCategory();
				throw new \Exception($previous_cat_array);
				

				foreach ($selected_categories as $cat) {

					$model_asso
							->addCondition('lead_id',$frm['contact_id'])
							->addCondition('marketing_category_id',$cat)
							->tryLoadAny()	
							->saveAndUnload();
				}

				$frm->save();
				$frm->js(null,$this->js()->univ()->successMessage('Lead associated with categories'))->reload()->execute();	
						
				});
			
		}


		// $activity_view = $this->add('xepan\marketing\View_activity',null,'activity');
	}

	function defaultTemplate(){
		return ['page/leadprofile'];
	}
}
