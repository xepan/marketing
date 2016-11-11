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
            ->addActivity("Sms : '".$this['title']."' Submitted For Approval",$this->id/* Related Document ID*/, /*Related Contact ID*/null,null,null,"xepan_marketing_addsms&0&action=view&document_id=".$this->id."")
            ->notifyWhoCan('reject,approve,test','Submitted');
        $this->saveAndUnload();    
	}

	function reject(){
		$this['status']='Rejected';
        $this->app->employee
            ->addActivity("Sms : '".$this['title']."' Rejected ",$this->id/* Related Document ID*/, /*Related Contact ID*/null,null,null,"xepan_marketing_addsms&0&action=view&document_id=".$this->id."")
            ->notifyWhoCan('submit,test','Rejected');
        $this->saveAndUnload();     
	}

	function approve(){
		$this['status']='Approved';
        $this->app->employee
            ->addActivity("Sms : '".$this['title']."' Approved ",$this->id/* Related Document ID*/, /*Related Contact ID*/null,null,null,"xepan_marketing_addsms&0&action=view&document_id=".$this->id."")
            ->notifyWhoCan('reject,schedule,test','Approved');
		$this->saveAndUnload(); 
	}
}
