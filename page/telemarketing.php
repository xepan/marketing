<?php

namespace xepan\marketing;

class page_telemarketing extends \xepan\base\Page{
	public $title = "Tele Marketing";
	function init(){
		parent::init();
		
		$lead_id = $this->app->stickyGET('lead_id');

		if($lead_id)
			$lead_model = $this->add('xepan\marketing\Model_Lead')->load($lead_id);
		/*
				GRID FOR SHOWING ALL LEAD 
		*/

		$view_lead = $this->add('xepan\hr\Grid',null, 'side',['view\teleleadselector']);
		$model_lead = $this->add('xepan\marketing\Model_Lead');
		$view_lead->setModel($model_lead, ['name','type','city','contacts_str']);
		$view_lead->add('xepan\base\Controller_Avatar',['options'=>['size'=>25,'border'=>['width'=>0]],'name_field'=>'name','default_value'=>'']);
		$view_lead->addPaginator(10);
		$frm = $view_lead->addQuickSearch(['name','contacts_str']);

		$status=$frm->addField('Dropdown','marketing_category_id')->setEmptyText('Categories');
		$status->setModel('xepan\marketing\MarketingCategory');

		$frm->addHook('applyFilter',function($f,$m){
			if($f['marketing_category_id']){
				$cat_asso = $this->add('xepan\marketing\Model_Lead_Category_Association');
				$cat_asso->addCondition('marketing_category_id',$f['marketing_category_id']);
				$m->addCondition('id','in',$cat_asso->fieldQuery('lead_id'));
			}
		});
		
		$status->js('change',$frm->js()->submit());

		$view_lead->js('click')->_selector('.do-view-lead')->univ()->frameURL('Lead Details',[$this->api->url('xepan_marketing_leaddetails'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);

		/*
				FORM FOR ADDING CONVERSATION 
		*/



		$model_telecommunication = $this->add('xepan\marketing\Model_TeleCommunication');
		// $model_telecommunication->getElement('direction')->defaultValue('Out');
		$view_teleform = $this->add('View',null,'top');
		$view_teleform_url = $this->api->url(null,['cut_object'=>$view_teleform->name]);
		
		$form = $view_teleform->add('Form');
		$form->setLayout('view\teleconversationform');
		
		$lead_name = $form->layout->add('View',null,'name')->set(isset($lead_model)?$lead_model['name']:'No Lead Selected');

		$form->setModel($model_telecommunication,['title','description','contacts_str',]);
		$form->addSubmit('Add Conversation')->addClass('btn btn-sm btn-primary');

		$button = $form->layout->add('Button',null,'btn-opportunity')->set('Opportunities')->addClass('btn btn-sm btn-primary');

		
		/*
				GRID FOR SHOWING PREVIOUS CONVERSATION 
		*/							

		$model_communication = $this->add('xepan\communication\Model_Communication')
									->addCondition('communication_type','telemarketing')
									->addCondition('to_id',$lead_id);
		$view_conversation = $this->add('xepan\hr\CRUD',['allow_add'=>false], 'bottom',['view\teleconversationlister']);
		$view_conversation->setModel($model_communication,['title','description','created_at','from'],['title','description','created_at','from_id']);
		$view_conversation_url = $this->api->url(null,['cut_object'=>$view_conversation->name]);
		$view_conversation->grid->addPaginator(10);
		$view_conversation->grid->addQuickSearch(['name']);
		
		/*
				JS FOR RELOAD WITH SPECIFIC ID 
		*/
		
		$view_lead->on('click','#lead',function($js,$data)use($view_conversation_url,$view_conversation,$view_teleform_url,$view_teleform){

			$js_array = [
					$view_conversation->js()->reload(['lead_id'=>$data['id']],null,$view_conversation_url),
					$view_teleform->js()->reload(['lead_id'=>$data['id']],null,$view_teleform_url),

					];
			return $js_array;
		});
		
		
		/*
				FORM SUBMISSION 
		*/

		if($form->isSubmitted()){

			if(!$lead_id){
				return $form->js()->univ()->errorMessage('Please Select A Lead First')->execute();
			}

			$form->model['title'] = $form['title']; 
			$form->model['description'] = $form['description']; 
			$form->model->addCondition('from_id', $this->app->employee->id);
			$form->model->addCondition('to_id', $lead_id); 
			$form->save();

			return $view_conversation->js(true,$form->js()->univ()->successMessage("Added"))->univ()->reload()->execute();
			  
		}

		/*
				VIRTUAL PAGE TO SEE AND ADD OPPORTUNITIES 
		*/	
 		$button->add('VirtualPage')
			->bindEvent('Opportunities','click')
			->set(function($page){
				$lead_id = $this->app->stickyGET('lead_id');
				if(!$lead_id){
					$page->add('View_Error')->set('Please Select A Lead First');
					return;	
				}
				$opportunity_model = $page->add('xepan\marketing\Model_Opportunity')
									  ->addCondition('lead_id',$lead_id);	
				$page->add('xepan\hr\CRUD',null,null,['grid\miniopportunity-grid'])->setModel($opportunity_model);

			});
	}

	function defaultTemplate(){
		return['page\telemarketing'];
	}
}