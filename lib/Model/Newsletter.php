<?php

namespace xepan\marketing;

class Model_Newsletter extends \xepan\marketing\Model_Content{

	function init(){
		parent::init();
		

		$this->getElement('status')->defaultValue('Draft');
		$this->addCondition('type','Newsletter');

		$this->addExpression('total_visitor')->set(function($m,$q){
			return $this->add('xepan\marketing\Model_LandingResponse')
					->addCondition('content_id',$q->getField('id'))
					->count();
		});
		
		$this->is([
			'message_blog|required'
			]);

		$this->addExpression('total_leads')->set(function($m,$q){
			return "'todo'";
		});

		$this->addExpression('send_to')->set(function($m,$q){
			return "'todo'";
		});

		$this->addExpression('remaining')->set(function($m,$q){
			return "'todo'";
		});

		$this->addExpression('name')->set($this->dsql()->expr('[0]',[$this->getElement('title')]));


	}

	function page_test($p){

		if(!$this->loaded())
			throw $this->exception('Newsletter must be saved before test','ValidityCheck')->setField('message_blog');
		$emails=[];

		// all emails for these employees post
		foreach ($this->app->employee->ref('post_id')->ref('EmailPermissions') as $ep) {
			$emails[]=$ep->ref('emailsetting_id')->get('email_username');
		}

	   // all email for this employee itself 
		foreach ($this->app->employee->ref('Emails') as $emp_emails) {
			$emails[]=$emp_emails['value'];
		}

		$post_emails= $this->add('xepan\hr\Model_Post_Email_MyEmails');

		$frm=$p->add('Form');

		$mymail = $frm->addField('Dropdown','email_username')->setEmptyText('Please Select From Email')->validate('required');
		$mymail->setModel('xepan\hr\Model_Post_Email_MyEmails');

		foreach ($emails as $email) {
			$frm->addField('checkbox',$this->api->normalizeName($email),$email);

		}
		$frm->addSubmit('Send');
		if($frm->isSubmitted()){
			$communication=$this->add('xepan\marketing\Model_Communication_Newsletter');	
			$communication->setRelatedDocument($this);

			$email_settings = $this->add('xepan\communication\Model_Communication_EmailSetting')->load($frm['email_username']);	
			$communication->setfrom($email_settings['from_email'],$email_settings['from_name']);

			$i=1;
			foreach ($emails as $email) {
				if(!$frm[$this->api->normalizeName($email)]) continue;
				if($i==1){
					$communication->addTo($email,$this->app->employee['name']);
				}else{
					$communication->addBcc($email);
				}
				$i++;
			}
			
			$subject=$this['title'];
			$email_body=$this['message_blog'];

			$email_subject=$this->add('GiTemplate');
			$email_subject->loadTemplateFromString($subject);
			$subject_v = $this->add('View',null,null,$email_subject);
			
			$temp=$this->add('GiTemplate');
			$temp->loadTemplateFromString($email_body);
			
			$body_v = $this->add('View',null,null,$temp);
			$contact=$this->app->employee;

			$body_v->setModel($contact);

			$communication->setSubject($subject_v->getHtml());
			$communication->setBody($body_v->getHtml());

			try{
				$communication->send($email_settings);
			}catch(\Exception $e){
				throw $e;
				
				return $this->api->js()->univ()->errorMessage($e->getMessage());
			}

			return $this->api->js()->univ()->successMessage('Email Send SuccessFully')->closeDialog();
		}
	}
	
	function page_schedule($p){
		if(!$this->loaded())
			throw new \Exception('Model not loaded');		
		
		$form = $p->add('Form');		
		$campaign_field = $form->addField('Dropdown','campaign');
		$campaign_field->validate('required');
		$campaign_field->setEmptyText('Please select a campaign')->setModel('xepan\marketing\Model_Campaign');
		$form->addField('DatePicker','date')->validate('required');
		$form->addField('TimePicker','time')->validate('required');
		$form->addSubmit('Schedule')->addClass('btn btn-primary btn-block');

		if($form->isSubmitted()){
			if(!$form['date'])				
				$form->error('date','Date field is mandatory');

			$schedule_time = date("H:i:s", strtotime($form['time']));
			$schedule_date = $form['date'].' '.$schedule_time;
			
			$campaign = $this->add('xepan\marketing\Model_Campaign');
			$schedule = $this->add('xepan\marketing\Model_Schedule');

			$schedule['campaign_id'] = $form['campaign'];
			$schedule['document_id'] = $this->id;
			$schedule['date'] = $schedule_date; 
			$schedule['client_event_id'] = '_fc'.uniqid(); 
			$schedule->save();
			
			$campaign->tryLoadBy('id',$form['campaign']);
			
			$old_schedule = json_decode($campaign['schedule'],true);
			$temp = Array ( 
				'title' => $this['title'], 
				'start' => $schedule_date, 
				'document_id' => $this->id, 
				'client_event_id' => $schedule['client_event_id'] 
			);
			
			$old_schedule[] = $temp;

			$campaign['schedule'] = json_encode($old_schedule);
			$campaign->save();

			return $form->js(null,$form->js()->closest('.dialog')->dialog('close'))->univ()->successMessage('Newsletter Scheduled')->execute();
		}
	}

	function submit(){
		$this['status']='Submitted';
        $this->app->employee
            ->addActivity("Submitted Newsletter", $this->id)
            ->notifyWhoCan('reject,approve,test','Submitted');
        $this->saveAndUnload();    
	}

	function reject(){
		$this['status']='Rejected';
        $this->app->employee
            ->addActivity("Rejected Newsletter", $this->id)
            ->notifyWhoCan('submit,test','Rejected');
        $this->saveAndUnload();     
	}

	function approve(){
		$this['status']='Approved';
        $this->app->employee
            ->addActivity("Approved Newsletter", $this->id)
            ->notifyWhoCan('reject,schedule,test','Approved');
		$this->saveAndUnload(); 
	}
}
