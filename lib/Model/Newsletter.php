<?php

namespace xepan\marketing;

class Model_Newsletter extends \xepan\marketing\Model_Content{


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
			return true;
		}

	}
}
