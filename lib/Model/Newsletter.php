<?php

namespace xepan\marketing;

class Model_Newsletter extends \xepan\marketing\Model_Content{

	function init(){
		parent::init();
		

		$this->getElement('status')->defaultValue('Draft');
		$this->addCondition('type','Newsletter');

	}

	function page_test($p){
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
			$communication->setSubject($this['title']);
			$communication->setBody($this['message_blog']);
			
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
			try{
				$communication->send($email_settings);
			}catch(\Exception $e){
				throw $e;
				
				$this->api->js()->univ()->errorMessage($e->getMessage())->execute();
			}

			return $this->api->js()->univ()->closeDialog()->execute();
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
            ->notifyWhoCan('reject,email,test','Approved');
		$this->saveAndUnload(); 
	}

}
