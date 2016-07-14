<?php

namespace xepan\marketing;

class Model_Sms extends \xepan\marketing\Model_Content{

	function init(){
		parent::init();
		

		$this->getElement('status')->defaultValue('Draft');
		$this->addCondition('type','Sms');

	}

	function submit(){
		$this['status']='Submitted';
        $this->app->employee
            ->addActivity("Submitted Sms", $this->id)
            ->notifyWhoCan('approve,reject,test','Submitted');
        $this->saveAndUnload();    
	}

	function reject(){
		$this['status']='Rejected';
        $this->app->employee
            ->addActivity("Rejected Sms", $this->id)
            ->notifyWhoCan('submit,test','Rejected');
        $this->saveAndUnload();     
	}

	function approve(){
		$this['status']='Approved';
        $this->app->employee
            ->addActivity("Approved Sms", $this->id)
            ->notifyWhoCan('schedule,reject,test','Approved');
		$this->saveAndUnload(); 
	}
}
