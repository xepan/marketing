<?php
namespace xepan\marketing;

class page_leadsource extends page_configurationsidebar{
	public $title = "Lead Source";
	function init(){
		parent::init();

		$config_m = $this->add('xepan\marketing\Model_Config_LeadSource');
		
		$config_m->add('xepan\hr\Controller_ACL');
		$config_m->tryLoadAny();

		$this->add('View')->set('Enter comma seperated values with no space');
		$form=$this->add('Form');
		$form->setModel($config_m,['lead_source']);
		$form->getElement('lead_source')->set($config_m['lead_source']);
		$form->addSubmit('Save')->addClass('btn btn-primary');
		
		if($form->isSubmitted()){
			$form->save();
			$form->js(null,$form->js()->reload())->univ()->successMessage('Information Saved')->execute();
		}
	}
}