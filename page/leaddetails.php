<?php

namespace xepan\marketing;

class page_leaddetails extends \xepan\base\Page {
	
	public $title ='Lead Details';
	public $breadcrumb=['Home'=>'index','Lead'=>'xepan_marketing_lead','Details'=>'#'];


	function init(){
		parent::init();

		$action = $this->api->stickyGET('action')?:'view';
		$lead= $this->add('xepan\marketing\Model_Lead');

		
		$lead->addExpression('weekly_communication')->set(function($m,$q){
			$comm = $m->add('xepan/communication/Model_Communication');
			// $comm->addCondition('sent_on','>',date('Y-m-d',strtotime('-8 week')));
			$comm->_dsql()->del('fields');
			$comm->_dsql()->field('count(*) communication_count');
			$comm->_dsql()->field('to_id');
			$comm->_dsql()->group('to_id');

			return $q->expr("(select GROUP_CONCAT(tmp.communication_count) from [sql] as tmp where tmp.to_id = [0])",[$m->getElement('id'),'sql'=>$comm->_dsql()]);
		});

		$lead->tryLoadBy('id',$this->api->stickyGET('contact_id'));
		
		if($action=="add"){

			$lead_view = $this->add('xepan\base\View_Contact',['acl'=>'xepan\marketing\Model_Lead','view_document_class'=>'xepan\hr\View_Document'],'contact_view_full_width');
			$lead_view->document_view->effective_template->del('im_and_events_andrelation');
			$lead_view->document_view->effective_template->del('email_and_phone');
			$lead_view->document_view->effective_template->del('avatar_wrapper');
			$lead_view->document_view->effective_template->del('contact_since_wrapper');
			$lead_view->document_view->effective_template->del('send_email_sms_wrapper');
			$lead_view->document_view->effective_template->del('online_status_wrapper');
			$lead_view->document_view->effective_template->del('contact_type_wrapper');
			$this->template->del('other_details');
			$lead_view->setStyle(['width'=>'50%','margin'=>'auto']);
		}else{
			$lead_view = $this->add('xepan\base\View_Contact',['acl'=>'xepan\marketing\Model_Lead','view_document_class'=>'xepan\hr\View_Document'],'contact_view');
		}

		$lead_view->setModel($lead);

			$detail = $this->add('xepan\hr\View_Document',['action'=> $action,'id_field_on_reload'=>'contact_id'],'details',['view/details']);
			$detail->setModel($lead,['source','marketing_category','communication','opportunities','remark','weekly_communication'],['source','remark']);//,'marketing_category_id','communication','opportunities'
		if($lead->loaded()){
			
			$opportunities_tab = $this->add('xepan\hr\View_Document',['action'=> $action,'id_field_on_reload'=>'contact_id'],'opportunity',['view/opp']);
			$o = $opportunities_tab->addMany('opportunity',null,'opportunity',['grid/addopportunity-grid']);
			$o->setModel($lead->ref('Opportunities'));
			

			$activity_view = $this->add('xepan\base\Grid',['no_records_message'=>'No activity found'],'activity',['view/activity/activity-grid']);
			$activity_view->add('xepan\base\Paginator',null,'Paginator');

			$activity=$this->add('xepan\base\Model_Activity')->setOrder('created_at','desc');
			$activity->addCondition('contact_id',$_GET['contact_id']);
			$activity->tryLoadAny();
			$activity_view->setModel($activity);	


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
		
		$this->js(true)->_load('jquery.sparkline.min')->_selector('.sparkline')->sparkline('html', ['enableTagOptions' => true]);
	}

	function defaultTemplate(){
		return ['page/leadprofile'];
	}

	function render(){
		// $this->app->jui->addStaticInclude('jquery.easypiechart.min');
		parent::render();
	}	
}
