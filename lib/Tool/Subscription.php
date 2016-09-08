<?php

namespace xepan\marketing;

class Tool_Subscription extends \xepan\cms\View_Tool{
	public $options = [
				'ask_name'=>false,
				'send_mail'=>false,
				'on_success'=>'Same Page',
				'success_url'=>'',
				'lead_category'=>'',
				'submit_button_name'=>'Submit',
				'show_as_popup' => false
			];	

	function init(){
		parent::init();
		
		$selected_category = [];
		if($this->options["lead_category"]){
			$selected_category = explode(",", $this->options["lead_category"]);
		}		

		$form = $this->add('Form',null,'form');
		$form->setLayout('view/tool/subscription');

		if($this->options['ask_name']){
			$form->addField('first_name')->addClass('form-control');
			$form->addField('last_name')->addClass('form-control');;
		}

		$form->addField('email')->addClass('form-control');;
		$form->addSubmit($this->options['submit_button_name'])->addClass('btn btn-primary btn-block');
		
		if($form->isSubmitted()){
			$ei = $this->add('xepan\base\Model_Contact_Email');
			$ei->tryLoadBy('value',$form['email']);

			if($ei->loaded()){
				$l_id = $ei['contact_id'];
				$l_model = $this->add('xepan\marketing\Model_Lead')->load($l_id);
				$cat_arr = $l_model->getAssociatedCategories();

				setcookie('xepan_lead_subscription',$form['email']);
				$cat_diff = array_merge(array_diff($cat_arr, $selected_category),array_diff($selected_category, $cat_arr));
				if(!count($cat_diff)){
					if(!isset($_COOKIE['xepan_lead_subscription']) AND ($this->options['show_as_popup'] === true))
						return $form->js(null,$form->js()->_selector('#'.$this->name."_subscription_model")->modal('hide'))->univ()->errorMessage('Already Subscribed')->execute();
					return $form->js()->univ()->errorMessage('Already Subscribed')->execute();
				}
								
				foreach ($cat_diff as $category) {					
					$association = $this->add('xepan\marketing\Model_Lead_Category_Association');
					
					$association['lead_id'] = $l_model->id;
					$association['marketing_category_id'] = $category;  
					$association->save();
				}

				if(!isset($_COOKIE['xepan_lead_subscription']) AND ($this->options['show_as_popup'] === true))
					return $form->js(null,$form->js()->_selector('#'.$this->name."_subscription_model")->modal('hide'))->univ()->successMessage('Done')->execute();
					
				return $form->js()->univ()->successMessage('Done')->execute();
			}				

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

				$point_system = $this->add('xepan\base\Model_PointSystem');	
				$point_system['score'] = '50';
				$point_system['created_at'] = $this->app->now;
				$point_system['contact_id'] = $lead->id;
				$point_system->save();

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
    		
    		setcookie('xepan_lead_subscription',$form['email']);
    		if($this->options['send_mail']){
    			$email_id = $form['email'];
    			$this->sendThankYouMail($email_id);
    		}

    		if($this->options['on_success'] == 'Same Page'){
    			if(!isset($_COOKIE['xepan_lead_subscription']) AND ($this->options['show_as_popup'] === true))
					return $form->js(null,$form->js()->_selector('#'.$this->name."_subscription_model")->modal('hide'))->univ()->successMessage('Done')->execute();
				
				return $form->js()->univ()->successMessage('Done')->execute();	
    		}else
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

	function defaultTemplate(){
		if($this->options['show_as_popup'])
			return['view\tool\modalsubscription'];
		else
			return['view\tool\primarysubscription'];
	}

	function render(){
		if(!isset($_COOKIE['xepan_lead_subscription']) AND ($this->options['show_as_popup'] === true))
			$this->js(true)->_selector('#'.$this->name."_subscription_model")->modal('show');
		else		
			$this->js(true)->_selector('#'.$this->name."_subscription_model")->modal('hide');

		parent::render();
	}
}