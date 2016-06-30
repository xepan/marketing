<?php

namespace xepan\marketing;

class Tool_Subscription extends \xepan\cms\View_Tool{
	public $options = [
				'ask_name'=>false,
				'send_mail'=>false,
				'on_success'=>'Same Page',
				'success_url'=>'',
				'lead_category'=>''
			];	

	function init(){
		parent::init();
		
		$selected_category = [];
		if($this->options["lead_category"]){
			$selected_category = explode(",", $this->options["lead_category"]);
		}		

		$form = $this->add('Form');
		$form->setLayout('tool/subscription');

		if($this->options['ask_name']){
			$form->addField('first_name');
			$form->addField('last_name');
		}

		$form->addField('email');
		$form->addSubmit('Submit');
		
		if($form->isSubmitted()){
			$lead = $this->add('xepan\marketing\Model_Lead');
			
			try{
				$this->api->db->beginTransaction();
				$lead['source'] = 'Subscription';
				
				if($this->options['ask_name']){
					$lead['first_name'] = $form['first_name'];
					$lead['last_name'] = $form['last_name'];
				}else{
					$lead['first_name'] = 'Guest';
					$lead['last_name'] = 'Visitor';
				}
				
				$lead->save();

				foreach ($selected_category as $cat) {					
					$assoc = $this->add('xepan\marketing\Model_Lead_Category_Association');
					
					$assoc['lead_id'] = $lead->id;
					$assoc['marketing_category_id'] = $cat;  
					$assoc->save();
				}

				$email_info = $this->add('xepan\base\Model_Contact_Email');
				$email_info['contact_id'] = $lead->id;
				$email_info['head'] = 'Official';
				$email_info['value'] = $form['email'];
				$email_info->save();
				$this->api->db->commit();
			}catch(\Exception $e){
				$this->api->db->rollback();
    			return $form->error('email','An unexpected error occured');
    		}	

    		if($this->options['send_mail']){
    			$email_id = $form['email'];
    			$this->sendThankYouMail($email_id);
    		}

    		if($this->options['on_success'] == 'Same Page')    					
				return $form->js()->univ()->successMessage('Done')->execute();
    		else
    			$this->app->redirect($this->app->url($this->options['success_url']));
		}
	}	

	function sendThankYouMail($email_id){
		$email_settings = $this->add('xepan\communication\Model_Communication_EmailSetting')->tryLoadAny();
		$mail = $this->add('xepan\communication\Model_Communication_Email');
		
		$sub_model=$this->app->epan->config;
		$email_subject=$sub_model->getConfig('SUBSCRIPTION_MAIL_SUBJECT');
		$email_body=$sub_model->getConfig('SUBSCRIPTION_MAIL_BODY');												

		$subject_temp=$this->add('GiTemplate');
		$subject_temp->loadTemplateFromString($email_subject);
		
		$subject_v=$this->add('View',null,null,$subject_temp);

		$temp=$this->add('GiTemplate');
		$temp->loadTemplateFromString($email_body);
		
		$body_v=$this->add('View',null,null,$temp);
		$body_v->template->trySet('username',$email_id);					

		$mail->setfrom($email_settings['from_email'],$email_settings['from_name']);
		$mail->addTo($email_id);
		$mail->setSubject($subject_v->getHtml());
		$mail->setBody($body_v->getHtml());
		$mail->send($email_settings);
	}	
}