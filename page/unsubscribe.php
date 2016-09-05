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
			$model_contact['status'] = 'InActive';
			$model_contact->save();

			$this->js(true)->univ()->successMessage('You have been unsubscribed');
		}
	}
}