<?php

namespace xepan\marketing;

/**
* 
*/
class View_TeleMarketingListView extends \View{
	
	function init(){
		parent::init();
		$contact_id = $this->app->stickyGET('contact_id');
		$lead = $this->add('xepan\marketing\Model_Lead');
		if($contact_id){
			$lead->load($contact_id);
		}
		
		if(!$contact_id){
			$this->add('View_Error',null,'title')->set('Please Select A Lead First')->addClass('xepan-padding bg-danger');
			$this->template->del('comm_wrapper');
			$this->template->del('detail_wrapper');
			return;	
		}else{
			$title = $this->add('View',null,'title')->setHtml("<h1>". $lead['name_with_type']." { Score: ".$lead['score']." }</h1>")->addClass('lead-score text-center');
			// $title->js('reload')->reload();
		}


		$comm_form = $this->add('xepan\communication\Form_Communication',null,'communication_form');
		$comm_form->setContact($lead);
		$member_phones = $lead->getPhones();
		$comm_form->getElement('email_to')->set($lead['emails_str']);
		$comm_form->getElement('notify_email_to')->set($lead['emails_str']);
		$comm_form->getElement('from_number')->set($lead['contacts_str']);
		$body_field = $comm_form->getElement('body');
		$body_field->options = ['toolbar1'=>"styleselect | bold italic fontselect fontsizeselect | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | forecolor backcolor",'menubar'=>false];
		$called_to_field = $comm_form->getElement('called_to');

		$comm_form->getElement('type')->set('Call');
		$comm_form->getElement('status')->set('Called');

		$nos=[];
		foreach ($member_phones as $no) {
			$nos[$no] = $no;
		}
		$called_to_field->setValueList($nos);

		// $comm_form->getElement('follow_up')->;
		// $comm_form->layout->template->del('score_button_wrapper');
		// $comm_form->layout->template->del('followup_form_wrapper');

		$view_conversation = $this->add('xepan\communication\View_Lister_Communication',['contact_id'=>$contact_id, 'type' =>'TeleMarketing'],'communication_list');

		$comm_form->addSubmit('Create Communication')->addClass('btn btn-success');
		if($comm_form->isSubmitted()){
			$communication_model = $comm_form->process();

			$reload_array=
						[
						$comm_form->js()->univ()->successMessage('Communication Created'),
						$view_conversation->js()->reload()						
						];

			$comm_form->js(null,$reload_array)->reload()->execute();
		}		

		$followup_form = $this->add('Form',null,'followup_form');
		
		$followup_form->addField('task_title');
		$starting_date_field = $followup_form->addField('DateTimePicker','starting_at');
		$starting_date_field->js(true)->val('');
		$assign_to_field = $followup_form->addField('DropDown','assign_to');
		$assign_to_field->setModel('xepan\hr\Model_Employee')->addCondition('status','Active');
		$assign_to_field->set($this->app->employee->id);
		$followup_form->addField('text','description');
		$followup_form->addSubmit('Follow up')->addClass('btn btn-info');

		if($followup_form->isSubmitted()){
			$model_task = $this->add('xepan\projects\Model_Task');
			$model_task['type'] = 'Followup';
			$model_task['task_name'] = $followup_form['task_title'];
			$model_task['created_by_id'] = $followup_form->app->employee->id;
			$model_task['starting_date'] = $followup_form['starting_at'];
			$model_task['assign_to_id'] = $followup_form['assign_to'];
			$model_task['description'] = $followup_form['description'];
			$model_task['related_id'] = $contact_id;
			$model_task->save();

			$followup_form->js(null,$followup_form->js()->reload())->univ()->successMessage('Followup Created')->execute();
		}

		$opportunity_model = $this->add('xepan\marketing\Model_Opportunity')
							  ->addCondition('lead_id',$contact_id);	
		$this->add('xepan\hr\CRUD',null,'opportunity_form',['grid\miniopportunity-grid'])->setModel($opportunity_model,['title','description','status','assign_to_id','fund','discount_percentage','closing_date'],['title','description','status','assign_to_id','fund','discount_percentage','closing_date']);

		$model_telecommunication = $this->add('xepan\marketing\Model_TeleCommunication');
		$view_teleform = $this->add('View',null,'top');
		$view_teleform_url = $this->api->url(null,['cut_object'=>$view_teleform->name]);
		
		// opportunity, filter form
		$form = $view_teleform->add('Form');
		$form->setLayout('view\teleconversationform');
		$type_field = $form->addField('xepan\base\DropDown','communication_type');
		$type_field->setAttr(['multiple'=>'multiple']);
		$type_field->setValueList(['TeleMarketing'=>'TeleMarketing','Email'=>'Email','Support'=>'Support','Call'=>'Call','Newsletter'=>'Newsletter','SMS'=>'SMS','Personal'=>'Personal']);
		$form->addField('search');
		$form->addSubmit('Filter')->addClass('btn btn-primary btn-block');



		$model_communication = $this->add('xepan\communication\Model_Communication');
		$model_communication->addCondition([['to_id',$contact_id],['from_id',$contact_id]]);

		$model_communication->setOrder('id','desc');

		if($form->isSubmitted()){												
			$view_conversation->js()->reload(['comm_type'=>$form['communication_type'],'search'=>$form['search']])->execute();
		}
		
		// FILTERS
		if($_GET['comm_type']){			
			$model_communication->addCondition('communication_type',explode(",", $_GET['comm_type']));
		}

		if($search = $this->app->stickyGET('search')){			
			$model_communication->addExpression('Relevance')->set('MATCH(title,description,communication_type) AGAINST ("'.$search.'")');
			$model_communication->addCondition('Relevance','>',0);
 			$model_communication->setOrder('Relevance','Desc');
		}

		$view_conversation->setModel($model_communication)->setOrder('created_at','desc');
		$view_conversation->add('Paginator',['ipp'=>10]);

		$temp = ['TeleMarketing','Email','Support','Call','Newsletter','SMS','Personal'];
		$type_field->set($_GET['comm_type']?explode(",", $_GET['comm_type']):$temp)->js(true)->trigger('changed');
		
		$form->on('click','.positive-lead',function($js,$data)use($lead,$model_communication,$title){
				$this->app->hook('pointable_event',['telemarketing_response',['lead'=>$lead,'comm'=>$model_communication,'score'=>true]]);
			
		$js_array = [
			$js->univ()->successMessage('Positive Marking Done'),
			$title->js()->_selector('.lead-score')->trigger('reload'),
			];
		return $js_array;
		});
		
		$form->on('click','.negative-lead',function($js,$data)use($lead,$model_communication,$title){
			$this->app->hook('pointable_event',['telemarketing_response',['lead'=>$lead,'comm'=>$model_communication],'score'=>false]);
			$js_array = [
			$js->univ()->successMessage('Negative Marking Done'),
			$title->js()->_selector('.lead-score')->trigger('reload'),
			];
		return $js_array;
		});

		$this->template->trySetHtml('name',$lead['name']);
		$this->template->trySetHtml('address',$lead['address']);
		$this->template->trySetHtml('city',$lead['city']);
		$this->template->trySetHtml('pin_code',$lead['pin_code']);
		$this->template->trySetHtml('state',$lead['state']);
		$this->template->trySetHtml('country',$lead['country']);
		$this->template->trySetHtml('contacts_str',$lead['contacts_str']);
		$this->template->trySetHtml('emails_str',$lead['emails_str']);
	}

	function defaultTemplate(){
		return ['view/telemarketing-list-view'];
	} 
}