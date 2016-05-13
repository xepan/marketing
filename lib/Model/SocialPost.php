<?php

namespace xepan\marketing;

class Model_SocialPost extends \xepan\marketing\Model_Content{


	function init(){
		parent::init();
		

		$this->getElement('status')->defaultValue('Draft');
		$this->addCondition('created_by_id',$this->app->employee->id);
		$this->addCondition('type','SocialPost');

	}

	function submit(){
		$this['status']='Submitted';
        $this->app->employee
            ->addActivity("Submitted Newsletter", $this->id)
            ->notifyWhoCan('approve,reject,test','Submitted');
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
            ->notifyWhoCan('email,reject,test','Approved');
		$this->saveAndUnload(); 
	}
}
