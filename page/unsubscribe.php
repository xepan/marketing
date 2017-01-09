<?php

namespace xepan\marketing;

class page_unsubscribe extends \Page{
	public $title = 'UnSubscribe';

	function init(){
		parent::init();

		$contact_id = $this->app->stickyGET('xepan_landing_contact_id');
		$document_id = $this->app->stickyGET('document_id');
		$email_str = $this->app->stickyGET('email_str');

		$reason = ['You Never Signed Up For This Mailing List'=>'You Never Signed Up For This Mailing List','The Emails Are Inappropriate'=>'The Emails Are Inappropriate','The Emails Are Spam And Should Be Reported'=>'The Emails Are Spam And Should Be Reported'];
		
		$form = $this->add('Form',null,'form');
		$form->addField('xepan\base\DropDownNormal','reason','If You Have A Moment, Please Let Us Know Why Are You Unsubscribing')->setValueList($reason)->setEmptyText('Please select a reason');
		$form->addField('text','other','Some Other Reason');
		$form->addSubmit('Unsubscribe')->addClass('btn btn-primary');

		if($form->isSubmitted()){			
			$model_contact = $this->add('xepan\base\Model_Contact');
			$model_contact->tryLoadBy('id',$contact_id);
			
			if(!$model_contact->loaded())
				$form->js(true)->univ()->errorMessage('Unexpected error occured')->execute();				
			
			$contact_emails = $model_contact->getEmails();
			$get_emails = [];
			$get_emails = explode(',',$email_str);

			if(!empty(array_intersect($contact_emails, $get_emails))){
				$model_contact->deactivateContactEmails($model_contact->id);
			}else{
				$form->js(true)->univ()->errorMessage('Email not in subscription list')->execute();				
			}								

			$unsubscribe_m = $this->add('xepan\marketing\Model_Unsubscribe');
			if($form['other'] == null)
				$unsubscribe_m['reason'] = $form['reason'];
			else{
				$unsubscribe_m['reason'] = $form['other'];
			}

			$unsubscribe_m['contact_id'] = $contact_id;
			$unsubscribe_m['document_id'] = $document_id;
			$unsubscribe_m['created_at'] = $this->app->now;
			$unsubscribe_m->save();
						
			$form->js(true)->univ()->successMessage('You have been unsubscribed')->execute();
		}
	}

	function defaultTemplate(){
		return ['page\unsubscribe'];
	}
}