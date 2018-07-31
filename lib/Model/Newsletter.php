<?php

namespace xepan\marketing;

class Model_Newsletter extends \xepan\marketing\Model_Content{

	function init(){
		parent::init();
		

		$this->getElement('status')->defaultValue('Draft');
		$this->addCondition('type','Newsletter');

		$this->getElement('content_name')->caption('Name');
		$this->getElement('title')->caption('Subject');

		$this->addExpression('total_visitor')->set(function($m,$q){
			return $this->add('xepan\marketing\Model_LandingResponse')
					->addCondition('content_id',$q->getField('id'))
					->count();
		})->sortable(true);
		
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
			$e_id = $ep->ref('emailsetting_id')->get('email_username');
			$emails[$e_id] = $e_id;
		}

	   // all email for this employee itself 
		foreach ($this->app->employee->ref('Emails') as $emp_emails) {
			$emails[$emp_emails['value']] = $emp_emails['value'];
		}

		$post_emails = $this->add('xepan\hr\Model_Post_Email_MyEmails');

		$frm = $p->add('Form');

		$mymail = $frm->addField('Dropdown','email_username','From Email id')
					->setEmptyText('Please Select From Email')
					->validate('required');
		$mymail->setModel($post_emails);

		$frm->add('View')->set("To Email Id's")->setStyle('margin-top','20px;');

		foreach ($emails as $email) {
			$frm->addField('checkbox',$this->api->normalizeName($email),$email);
		}
		$frm->addSubmit('Send');

		if($frm->isSubmitted()){
			$communication = $this->add('xepan\marketing\Model_Communication_Newsletter');
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
			
			$subject = $this['title'];
			$email_body = $this['message_blog'];

			$email_subject = $this->add('GiTemplate');
			$email_subject->loadTemplateFromString($subject);
			$subject_v = $this->add('View',null,null,$email_subject);
			
			$temp = $this->add('GiTemplate');
			$temp->loadTemplateFromString($email_body);
			
			$body_v = $this->add('View',null,null,$temp);
			$contact = $this->add('xepan\hr\Model_Employee')->load($this->app->employee->id);
			// $contact = $this->app->employee;
			
			$body_v->setModel($contact);
			$body_v->template->trySetHTML('unsubscribe','<a href='.$_SERVER["HTTP_HOST"].'/?page=xepan_marketing_unsubscribe&email_str='.$email.'&xepan_landing_contact_id='.$this->app->employee->id.'>Unsubscribe<a/>');
			$communication->setSubject($subject_v->getHtml());
			$communication->setBody($body_v->getHtml());

			try{
				$communication->send($email_settings,null,false);
			}catch(\Exception $e){
				throw $e;
				
				return $this->api->js()->univ()->errorMessage($e->getMessage());
			}

			return $this->api->js()->univ()->successMessage('Email Send SuccessFully')->closeDialog();
		}
	}

	function submit(){
		$this['status']='Submitted';
        $this->app->employee
            ->addActivity("Newsletter : '".$this['title']."' Submitted For Approval",$this->id/* Related Document ID*/, /*Related Contact ID*/null,null,null,"xepan_marketing_newsletterdesign&0&action=view&document_id=".$this->id."")
            ->notifyWhoCan('reject,approve,test','Submitted');
        $this->saveAndUnload();    
	}

	function reject(){
		$this['status']='Rejected';
        $this->app->employee
            ->addActivity("Newsletter : '".$this['title']."' Rejected ",$this->id/* Related Document ID*/, /*Related Contact ID*/null,null,null,"xepan_marketing_newsletterdesign&0&action=view&document_id=".$this->id."")
            ->notifyWhoCan('submit,test','Rejected');
        $this->saveAndUnload();     
	}

	function approve(){
		$this['status']='Approved';
        $this->app->employee
            ->addActivity("Newsletter : '".$this['title']."' Approved ",$this->id/* Related Document ID*/, /*Related Contact ID*/null,null,null,"xepan_marketing_newsletterdesign&0&action=view&document_id=".$this->id."")
            ->notifyWhoCan('reject,schedule,test','Approved');
		$this->saveAndUnload(); 
	}
}
