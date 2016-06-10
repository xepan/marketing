<?php

/**
* description: ATK Page
* 
* @author : Gowrav Vishwakarma
* @email : gowravvishwakarma@gmail.com, info@xavoc.com
* @website : http://xepan.org
* 
*/

namespace xepan\marketing;

class page_newsletterexec extends \xepan\base\Page {
	public $title='Cron to send NewsLetters';

	function init(){
		parent::init();

		/***************************************************************************
			Joining tables to find lead->categories->campaigns->schedule->content
		***************************************************************************/
		$leads = $this->add('xepan\marketing\Model_Lead');
		
		$lead_cat_assos_j = $leads->join('lead_category_association.lead_id');
		$camp_cat_assos_j = $lead_cat_assos_j->join('campaign_category_association.marketing_category_id','marketing_category_id');
				
		$camp_j = $camp_cat_assos_j->join('campaign.document_id','campaign_id');
		$camp_j->addField('campaign_title','title');
		$camp_j->addField('campaign_type');
		
		$schedule_j = $camp_j->join('schedule.campaign_id','document_id');
		$schedule_j->hasOne('xepan/marketing/Content','document_id','title');

		$schedule_j->addField('schedule_date','date');
		$schedule_j->addField('schedule_day','day');

		
		$comm_j = $schedule_j->leftJoin('communication.related_id','document_id');
		$schedule_j->addField('date');


	// 	/***************************************************************************
	// 		Expression for finding total days since lead joined
	// 	***************************************************************************/
		$leads->addExpression('days_from_join')->set(function($m,$q){
			return $m->dsql()->expr("DATEDIFF('[1]',[0])",[$m->getElement('created_at'),$this->api->today]);
		});


		/***************************************************************************
			Expression to find if the lead is 'Hot'/'sendable limit'
		***************************************************************************/
		$leads->addExpression('sendable')->set(function($m,$q){
			return $q->expr(
					"IF([0]='campaign',
						if([1]<='[2]',1,0),
						if([3]>=[4],1,0)
						)",
					[
						/* 0 */ $m->getElement('campaign_type'),
						/* 1 */ $m->getElement('schedule_date'),
						/* 2 */ $this->app->now,
						/* 3 */ $m->getElement('days_from_join'),
						/* 4 */ $m->getElement('schedule_day')
					]
					);
		})->type('boolean');


	// 	/***************************************************************************
	// 		To find the last newsletter sending time.
	// 	***************************************************************************/	
		$leads->addExpression('last_sent_newsletter_from_schedule_row_days')->set(function($m,$q){
			return $q->expr("(DATEDIFF('[1]',IFNULL([0],'1970-01-01')))",
				[
				$this->add('xepan\marketing\Model_Communication_Newsletter')
					->addCondition('related_id',$m->getElement('document_id'))
					->fieldQuery('created_at'),
				$this->app->now
				]);
		});


	// 	/***************************************************************************
	// 		Expression to extract 'message_3000' field from content model
	// 	***************************************************************************/
		$leads->addExpression('body')->set(function($m,$q){
			return $m->refSQL('document_id')->fieldQuery('message_blog');
		});

		$leads->addCondition('last_sent_newsletter_from_schedule_row_days','>=',10);
		$leads->addCondition('sendable',true);


		/***************************************************************************
			Sending newsletter
		***************************************************************************/
		
		$model = $this->add('xepan\marketing\Model_MassMailing');/*Mass Email Active*/
		
		$form = $this->add('Form');
		$form->addSubmit('Send Newsletter')->addClass('btn btn-primary');

		if($form->isSubmitted()){
			$email_settings = $this->add('xepan\communication\Model_Communication_EmailSetting')->tryLoadAny();
			$mailer = new \Nette\Mail\SmtpMailer(array(
			        'host' => $email_settings['email_host'],
			        'username' => $email_settings['email_username'],
			        'password' => $email_settings['email_password'],
			        'secure' => $email_settings['encryption'],
			        'persistent'=>true
				));
			/*******************************************************************
			        For each lead run this code
		    *******************************************************************/
			foreach ($leads as $lead) {
				$model_communication_newsletter = $this->add('xepan\marketing\Model_Communication_Newsletter');
				$model_communication_newsletter->setfrom($email_settings['from_email'],$email_settings['from_name']);
				// $email_lead=$this->add('xepan\marketing\Model_Lead')->load($lead->id);
				$emails = $lead->getEmails();
			    $subject = $lead['document'] ;		    		    
				$email_body = $lead['body'];
				/***************************************************
			         for each email of a particular lead 
		        ***************************************************/	
				// var_dump($emails);
				// exit;			         
				$email_subject=$this->add('GiTemplate');
				$email_subject->loadTemplateFromString($subject);
				$subject_v=$this->add('View',null,null,$email_subject);
				$subject_v->template->set($lead->get());
				
				$temp=$this->add('GiTemplate');
				$temp->loadTemplateFromString($email_body);
				$body_v=$this->add('View',null,null,$temp);
				$body_v->template->set($lead->get());

				foreach ($emails as $email) {	
					$model_communication_newsletter->addTo($email);
					// var_dump($email);
				}

				$model_communication_newsletter->setSubject($subject_v->getHtml());
				$model_communication_newsletter->setBody($body_v->getHtml());
				$model_communication_newsletter->send($email_settings, $mailer);
			}

			return $form->js()->univ()->successMessage('Newsletter Send')->execute();
		}

	}
}