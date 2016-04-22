<?php

namespace xepan\marketing;

class page_telemarketing extends \Page{
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
		$view_lead->setModel($model_lead, ['name']);
		$view_lead->add('xepan\base\Controller_Avatar',['options'=>['size'=>25,'border'=>['width'=>0]],'name_field'=>'name','default_value'=>'']);
		$view_lead->addPaginator(7);
		$frm = $view_lead->addQuickSearch(['name']);

		/*
				FORM FOR ADDING CONVERSATION 
		*/



		$model_telecommunication = $this->add('xepan\marketing\Model_TeleCommunication');
		
		$view_teleform = $this->add('View',null,'top');
		$view_teleform_url = $this->api->url(null,['cut_object'=>$view_teleform->name]);
		
		$form = $view_teleform->add('Form');
		$form->setLayout('view\teleconversationform');
		
		$lead_name = $form->layout->add('View',null,'name')->set(isset($lead_model)?$lead_model['name']:'No Lead Selected');

		$form->setModel($model_telecommunication,['title','description']);
		$form->addSubmit('Add Conversation');
		


									
		
		/*
				GRID FOR SHOWING PREVIOUS CONVERSATION 
		*/							

		$model_communication = $this->add('xepan\communication\Model_Communication')
									->addCondition('communication_type','telemarketing')
									->addCondition('to_id',$lead_id);
		$view_conversation = $this->add('xepan\hr\CRUD',['allow_add'=>false], 'bottom',['view\teleconversationlister']);
		$view_conversation->setModel($model_communication,['title','description','created_at','from'],['title','description','created_at','from_id']);
		$view_conversation_url = $this->api->url(null,['cut_object'=>$view_conversation->name]);
		
		/*
				JS FOR RELOAD WITH SPECIFIC ID 
		*/
		
		$view_lead->on('click','.lead',function($js,$data)use($view_conversation_url,$view_conversation,$view_teleform_url,$view_teleform){

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

			if(!$lead_id)
				throw new \Exception("Please Select A Lead First");
			$form->model['title'] = $form['title']; 
			$form->model['description'] = $form['description']; 
			$form->model->addCondition('from_id', $this->app->employee->id);
			$form->model->addCondition('to_id', $lead_id); 
			$form->save();

			return $view_conversation->js(true,$form->js()->univ()->successMessage("Added"))->univ()->reload()->execute();
			  
		}
	}

	function defaultTemplate(){
		return['page\telemarketing'];
	}
}