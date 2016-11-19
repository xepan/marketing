<?php

namespace xepan\marketing;

class page_unsubscribe extends \Page{
	public $title = 'UnSubscribe';

	function init(){
		parent::init();
		$contact_id = $this->app->stickyGET('xepan_landing_contact_id');
		$model_contact = $this->add('xepan\base\Model_Contact');
		$model_contact->tryLoadBy('id',$contact_id);

		if($model_contact->loaded()){			
			$contact_emails = $model_contact->getEmails();
			$get_emails = [];
			$get_emails = explode(',', $this->app->stickyGET('email_str'));

			if(!empty(array_intersect($contact_emails, $get_emails)))				
				$model_contact->deactivateContactEmails($model_contact->id);
				$model_contact->save();
				$this->js(true)->univ()->successMessage('You have been unsubscribed');
		}
		
		$subscription_tool = $this->add('xepan\marketing\Tool_Subscription',null,'subscription');
	}

	function defaultTemplate(){
		return ['page\unsubscribe'];
	}
}