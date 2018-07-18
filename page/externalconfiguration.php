<?php

namespace xepan\marketing;

class page_externalconfiguration extends \xepan\base\Page{
	public $title = "External/API Configuration";
	function init(){
		parent::init();

		$config_m = $this->add('xepan\base\Model_ConfigJsonModel',
		[
			'fields'=>[
						'activate_lead_api'=>'checkbox',
						'open_lead_external_info_in_iframe'=>'checkbox',
						'external_url'=>'Line',
						],
				'config_key'=>'MARKETING_EXTERNAL_CONFIGURATION',
				'application'=>'marketing'
		]);

		// $config_m->add('xepan\hr\Controller_ACL');
		$config_m->tryLoadAny();

		$tabs = $this->add('Tabs');
        $lead_tab = $tabs->addTab('Leads');

        $dir = new \DirectoryIterator('vendor/xepan/marketing/lib/Controller/APIConnector/');
		foreach ($dir as $fileinfo) {
		    if (!$fileinfo->isDot()) {
		    	$api = $fileinfo->getFilename();
		    	$api = str_replace(".php", '', $api);
		        $tab = $tabs->addTab($api);
		        $cont = $this->add('xepan\marketing\Controller_APIConnector_'.$api);
		        $cont->config($tab);

		    }
		}

        $lead_form = $lead_tab->add('Form');
        $lead_form->setModel($config_m,['activate_lead_api','open_lead_external_info_in_iframe','external_url']);
        $lead_form->addSubmit('Save')->addClass('btn btn-info');

        $lead_form->getElement('external_url');

        if($lead_form->isSubmitted()){
        	$lead_form->save();
        	$lead_form->js()->univ()->successMessage('Saved')->execute();
        }
	}
}