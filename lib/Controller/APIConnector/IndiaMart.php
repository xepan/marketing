<?php

namespace xepan\marketing;

class Model_Config_IndiaMart extends \xepan\base\Model_ConfigJsonModel{
	public $fields =[
								'registered_mobile_number'=>"Line",
								'crm_key'=>"Line",
								'check_frequency_in_hours'=>"number",
								'last_checked'=>"date",
								'source_to_be_set'=>"DropDown",
								];
	public $config_key = 'INDIAMART_CONFIG';
	public $application='marketing';

	function init(){
		parent::init();

		$this->getElement('last_checked')->display(['form'=>'DatePicker']);

		$source_model = $this->add('xepan\marketing\Model_Config_LeadSource');
		$source_model->tryLoadAny();
		$sources=explode(",",$source_model['lead_source']);
		array_walk($sources,'trim');
		$this->getElement('source_to_be_set')->setValueList(array_combine($sources, $sources));

	}

}

class Controller_APIConnector_IndiaMart extends \AbstractController {
	
	// http://mapi.indiamart.com/wservce/enquiry/listing/GLUSR_MOBILE/7042445112/GLUSR_MOBILE_KEY/NzA0MjQ0NTExMiMxMDM1OTU4MA==/Start_Time/06-OCT-2017/End_Time/25-OCT-2017/
	public $api_key = 'http://mapi.indiamart.com/wservce/enquiry/listing/GLUSR_MOBILE/{$registered_mobile_number}/GLUSR_MOBILE_KEY/{$crm_key}/Start_Time/{$start_date}/End_Time/{$end_date}/';

	function init(){
		parent::init();

		$this->config = $this->add('xepan\marketing\Model_Config_IndiaMart')->tryLoadAny();
	}

	function config($page){

		$url = $this->api_key;

		$url = str_replace('{$registered_mobile_number}', $this->config['registered_mobile_number'], $url);
		$url = str_replace('{$crm_key}', $this->config['crm_key'], $url);
		$url = str_replace('{$start_date}', date('d-M-Y',strtotime($this->config['last_checked'])), $url);
		$url = str_replace('{$end_date}', date('d-M-Y',strtotime($this->app->now)), $url);

		$page->add('View_Error')->set('Under development, not for production use');
		$page->add('View_Info')->set($url);

		$this->config->addHook('beforeSave',function($m){
			$m['check_frequency_in_hours'] = (int) $m['check_frequency_in_hours'];
		},[],3);

		$form = $page->add('Form');
		$form->setModel($this->config);
		$form->addSubmit('Update')->addClass('btn btn-primary');

		$page->add('HR');
		$page->add('Button')
			->set('Execute Now')
			->addClass('btn btn-primary btn-block')
			->on('click',function($js,$data){
				$this->execute();
				return $js->univ()->successMessage('Executed');
			});

		if($form->isSubmitted()){
			$form->save();
			$form->js()->reload()->univ()->successMessage('Config Updated')->execute();
		}
	}

	function execute(){
		$url = $this->api_key;

		$url = str_replace('{$registered_mobile_number}', $this->config['registered_mobile_number'], $url);
		$url = str_replace('{$crm_key}', $this->config['crm_key'], $url);
		$url = str_replace('{$start_date}', date('d-M-Y',strtotime($this->config['last_checked'])), $url);
		$url = str_replace('{$end_date}', date('d-M-Y',strtotime($this->app->now)), $url);

		$curl=$this->add('xepan\communication\Controller_CURL',['getHeaders'=>false]);
		$output = $curl->get($url);	
		var_dump(json_decode($output));
		// throw new \Exception('$output', 1);
		

	}

}