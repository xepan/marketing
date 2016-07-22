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
            ->addActivity("Sms '".$this['title']."' has been submitted ", $this->id)
            ->notifyWhoCan('reject,approve,test','Submitted');
        $this->saveAndUnload();    
	}

	function reject(){
		$this['status']='Rejected';
        $this->app->employee
            ->addActivity("Sms '".$this['title']."' rejected ", $this->id)
            ->notifyWhoCan('submit,test','Rejected');
        $this->saveAndUnload();     
	}

	function approve(){
		$this['status']='Approved';
        $this->app->employee
            ->addActivity("Sms '".$this['title']."' approved ", $this->id)
            ->notifyWhoCan('reject,schedule,test','Approved');
		$this->saveAndUnload(); 
	}
}
