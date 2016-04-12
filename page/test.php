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

class page_test extends \Page {
	public $title='Cron to send NewsLetters';

	function init(){
		parent::init();

		/***************************************************************************
			Joining tables to find lead->categories->campaigns->schedule->content
		***************************************************************************/
		$lead = $this->add('xepan\marketing\Model_Lead');
		
		$lead_cat_assos_j = $lead->join('lead_category_association.lead_id');
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


		/***************************************************************************
			Expression for finding total days since lead joined
		***************************************************************************/
		$lead->addExpression('days_from_join')->set(function($m,$q){
			return $m->dsql()->expr("DATEDIFF('[1]',[0])",[$m->getElement('created_at'),$this->api->today]);
		});


		/***************************************************************************
			Expression to find if the lead is 'Hot'/'sendable limit'
		***************************************************************************/
		$lead->addExpression('sendable')->set(function($m,$q){
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


		/***************************************************************************
			To find the last newsletter sending time.
		***************************************************************************/	
		$lead->addExpression('last_sent_newsletter_from_schedule_row_days')->set(function($m,$q){
			return $q->expr("(DATEDIFF('[1]',IFNULL([0],'1970-01-01')))",
				[
				$this->add('xepan\marketing\Model_Communication_Newsletter')
					->addCondition('related_id',$m->getElement('document_id'))
					->fieldQuery('created_at'),
				$this->app->now
				]);
		});


		/***************************************************************************
			Expression to extract 'message_3000' field from content model
		***************************************************************************/
		$lead->addExpression('body')->set(function($m,$q){
			return $m->refSQL('document_id')->fieldQuery('message_3000');
		});

		$lead->addCondition('last_sent_newsletter_from_schedule_row_days','>=',10);
		$lead->addCondition('sendable',true);


		/***************************************************************************
			Sending newsletter
		***************************************************************************/
		$model_communication_newsletter = $this->add('xepan\marketing\Model_Communication_Newsletter');
		$email_settings = $this->add('xepan\base\Model_Epan_EmailSetting')->tryLoadAny();
		$model_communication_newsletter->setfrom($email_settings['from_email'],$email_settings['from_name']);
		$model = $this->add('xepan\marketing\Model_MassMailing');/*Mass Email Active*/
		
		$form = $this->add('Form');
		$form->addSubmit('Send Newsletter');

		if($form->isSubmitted()){
			/*******************************************************************
			        For each lead run this code
		    *******************************************************************/
			foreach ($lead as $newsletter) {
				
				$emails = $lead->getEmails();
			    $subject = $lead['document'] ;		    		    
				$body = $lead['body'];
				/***************************************************
			         for each email of a particular lead 
		        ***************************************************/	
				foreach ($emails as $email) {	
					$model_communication_newsletter->addTo($email);
				}

				$model_communication_newsletter->setSubject($subject);
				$model_communication_newsletter->setBody($body);
				// $model_communication_newsletter->send($email_settings);
				$model_communication_newsletter->save();
			}

			return $form->js()->univ()->successMessage('Newsletter Send')->execute();
		}

	}
}

// foreach($mass_email_id as email_id){
// 	function sendAndCalculate($email_id);
// }

// function sendAndCalculate($email_id){
// 	if (count == queue)
// 	return;
// 	foreach(newsletter){
// 		if(time limit exceed)
// 			return;
// 		send mails;
// 		count ++;	
// 		}
// 	}
