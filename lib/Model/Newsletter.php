<?php

namespace xepan\marketing;

class Model_Newsletter extends \xepan\marketing\Model_Content{
	public $status=[
		'Draft',
		'Submitted',
		'Approved',
		'Rejected'
	];

	function init(){
		parent::init();
		

		$this->getElement('status')->defaultValue('Draft');

		$this->addCondition('type','Newsletter');
	}

	function page_test($p){
		$p->add('View')->set('Hello');
		$emails=[];

		// all emails for these employees post
		foreach ($this->app->employee->ref('post_id')->ref('EmailPermissions') as $ep) {
			$emails[]=$ep->ref('emailsetting_id')->get('email_username');
		}

	   // all email for this employee itself 
		foreach ($this->app->employee->ref('Emails') as $emp_emails) {
			$emails[]=$emp_emails['value'];
		}


		$frm=$p->add('Form');
		foreach ($emails as $email) {
			$frm->addField('checkbox',$this->api->normalizeName($email),$email);

		}
		$frm->addSubmit('Send');
		if($frm->isSubmitted()){
			$communication=$this->add('xepan\marketing\Model_Communication_Newsletter');	
			$communication->setSubject($this['title']);
			$communication->setBody($this['message_3000']);
			
			$communication->setRelatedDocument($this);

			$i=1;
			foreach ($emails as $email) {
				if(!$frm[$this->api->normalizeName($email)]) continue;
				if($i==1){
					$communication->addTo($email,$this->employee['name']);
				}else{
					$communication->addBcc($email);
				}
				$i++;
			}
			try{
				$communication->send();
			}catch(\Exception $e){
				$this->api->js()->univ()->errorMessage($e->getMessage())->execute();
			}

			return $this->api->js()->univ()->closeDialog()->execute();
		}

	}

	function submit(){
		$this['status']='Submitted';
        $this->app->employee
            ->addActivity("Submitted Newsletter", $this->id)
            ->notifyWhoCan('approve,reject','Submitted');
        $this->saveAndUnload();    
	}

	function reject(){
		$this['status']='Rejected';
        $this->app->employee
            ->addActivity("Rejected Newsletter", $this->id)
            ->notifyWhoCan('submit','Rejected');
        $this->saveAndUnload();     
	}

	function approve(){
		$this['status']='Approved';
        $this->app->employee
            ->addActivity("Approved Newsletter", $this->id)
            ->notifyWhoCan('?????','Approved');
		$this->saveAndUnload(); 
	}

}
