<?php

namespace xepan\marketing;

class Model_Config_IndiaMart extends \xepan\base\Model_ConfigJsonModel{
	public $fields =[
								'registered_mobile_number'=>"Line",
								'crm_key'=>"Line",
								'check_frequency_in_hours'=>"Number",
								'last_checked'=>"Date",
								'source_to_be_set'=>"DropDown",
								'associate_with_category'=>"DropDown",
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
		$this->getElement('associate_with_category')->setModel('xepan\marketing\Model_MarketingCategory');

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

		$data = json_decode($output,true);
		$count = 0;
		foreach ($data as $record){
			$email_ids = [$record['SENDEREMAIL'],$record['EMAIL_ALT']];
			$phone_nos = [$record['MOB'],$record['MOBILE_ALT'],$record['PHONE'],$record['PHONE_ALT']];
			$created_date = date('Y-m-d H:i:s',strtotime($record['DATE_TIME_RE']));

			// check config duplicate allowed
			
			// creating lead
			$lead_model = $this->add('xepan\marketing\Model_Lead');
			$lead_model['first_name'] = $record['SENDERNAME'];
			$lead_model['organization']= $record['GLUSR_USR_COMPANYNAME'];
			$lead_model['address'] = $record['ENQ_ADDRESS'];
			$lead_model['city'] = $record['ENQ_CITY'];
			$lead_model['state_id'] = $this->add('xepan\base\Model_State')->addCondition('name',$record['ENQ_STATE'])->tryLoadAny()->id;
			$lead_model['country_id'] = $this->add('xepan\base\Model_Country')->addCondition('iso_code',$record['COUNTRY_ISO'])->tryLoadAny()->id;
			$lead_model['remark'] = 'Auto Created From India Mart and Data are: '.json_encode($record);
			$lead_model['source'] = $this->config['source_to_be_set'];
			$lead_model['status'] = "Active";
			$lead_model['created_at'] = $created_date;
			$lead_model->save();

			$count++;
			// company email
			foreach ($email_ids as $email_id) {
				if(trim($email_id)){
					$email = $this->add('xepan\base\Model_Contact_Email');
					$email->addCondition('contact_id',$lead_model->id);
					$email->addCondition('value',$email_id);
					$email->tryLoadAny();
					if(!$email['head']) $email['head'] = "Official";
					$email->save();
				}
			}
			// company phone
			foreach ($phone_nos as $phone_no) {
				if(trim($phone_no)){
					$phone = $this->add('xepan\base\Model_Contact_Phone');
					$phone->addCondition('contact_id',$lead_model->id);
					$phone->addCondition('value',$phone_no);
					$phone->tryLoadAny();
					if(!$phone['head']) $phone['head'] = "Official";
					$phone->save();
				}
			}
			// associate lead
			if($this->config['associate_with_category']){
				$cat_asso_model = $this->add('xepan\marketing\Model_Lead_Category_Association');
				$cat_asso_model->addCondition('lead_id',$lead_model->id);
				$cat_asso_model->addCondition('marketing_category_id',$this->config['associate_with_category']);
				$cat_asso_model->tryLoadAny();
				if(!$cat_asso_model->loaded())
					$cat_asso_model['created_at'] = $this->app->now;
				$cat_asso_model->save();
			}

			$subject = $record['SUBJECT']." ".$record['PRODUCT_NAME'];
			$comment_model = $this->add('xepan\communication\Model_Communication_Comment');
			$comment_model->addCondition('from_id',$lead_model->id);
			$comment_model->addCondition('created_at',$created_date);
			$comment_model->tryLoadAny();

			if(!$comment_model->loaded()){
				// create comment type communication
				$this->add('xepan\communication\Model_Communication_Comment')
					->createNew($lead_model,$this->app->employee,$subject,$record['ENQ_MESSAGE'],$created_date);
			}


		}	

		$this->app->js(true)->univ()->successMessage('Total Record Fetched '.$count);
	}


}