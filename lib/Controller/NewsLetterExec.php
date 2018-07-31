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

class Controller_NewsLetterExec extends \AbstractController {
	
	public $title='Cron to send NewsLetters';
	public $debug = false;

	function init(){
		parent::init();
		// $this->app->today = '2016-09-12';
		// $this->app->now = $this->app->today.' 00:00:00';

		if($_GET['debug']){
			$this->debug=true;
		}

		$this->app->skipActivityCreate = true;
		
		$leads = $this->add('xepan\marketing\Model_Campaign_ScheduledNewsletters');

		$leads->addCondition('sendable',true);
		$leads->addCondition('campaign_status','Approved');
		$leads->addCondition('content_status','Approved');
		$leads->addCondition('document_type','Newsletter');
		$leads->addCondition('is_already_sent',false);
		
		
		

		// throw new \Exception($leads->count()->getOne());
		// $this->owner->add('Grid')->setModel($leads,['name']);	
		// return;
	// 	/***************************************************************************
	// 		Must have a gap of N days between sending this Content/Newsletter again
	// 	/***************************************************************************
		// $leads->addCondition('last_sent_newsletter_from_schedule_row_days','>=',10);

		/***************************************************************************
			Sending newsletter
		***************************************************************************/
		
		// $model = $this->add('xepan\marketing\Model_MassMailing');/*Mass Email Active*/
		
		// $form = $this->add('Form');
		// $form->addSubmit('Send Newsletter')->addClass('btn btn-primary');

		// if($form->isSubmitted()){
			$email_settings_temp = $this->add('xepan\communication\Model_Communication_EmailSetting')
									->addCondition('mass_mail',true)
									->addCondition('is_active',true);

			$total_send_limit = 0;
			foreach ($email_settings_temp as $es) {
				$total_send_limit += $es['email_threshold'];
			}

			$email_settings = $this->add('xepan\communication\Model_Communication_EmailSetting')
			->addCondition('mass_mail',true)
			->addCondition('is_active',true)
			->tryLoadAny();
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
			$leads->setLimit($total_send_limit);
			

			if($this->debug){
				$grid = $this->owner->add('Grid');
				$grid->setModel($leads->debug(),['name','campaign_title','document','schedule_date','days_from_join','last_sent_newsletter_date','last_sent_newsletter_from_schedule_row_days','campaign_status','content_status','sendable','is_already_sent','document_type']);
				$grid->addFormatter('document','wrap');
				$grid->addFormatter('name','wrap');

				return;
				// echo (string) $leads->debug()->_dsql();
				// $leads->debug()->tryLoadAny();
				// return;
			}

			$loop_count=1;
			// // just for test :: $leads = $this->add('xepan\marketing\Model_Lead')->setLimit(10);
			$done_contact_newsletter=[];
			foreach ($leads as $lead) {
				// throw new \Exception("Error Processing Request", 1);
				// throw new \Exception($lead->id, 1);
				// echo $lead['name']. '<br/>';
				if(in_array($lead['id'].$lead['document_id'], $done_contact_newsletter)) continue;

				// echo "working on ". $email_settings['name']. '<br/>';
			    if(!$email_settings->isUsable()){
			    	if($email_settings->loadNextMassEmail()){
				    	$mailer = new \Nette\Mail\SmtpMailer(array(
					        'host' => $email_settings['email_host'],
					        'username' => $email_settings['email_username'],
					        'password' => $email_settings['email_password'],
					        'secure' => $email_settings['encryption'],
					        'persistent'=>true
						));
			    	}else{
			    		echo "No more email_settings <br/>";
			    		break; // No more email setting found
			    	}
			    }

				$model_communication_newsletter = $this->add('xepan\marketing\Model_Communication_Newsletter');
				$model_communication_newsletter->setfrom($email_settings['from_email'],$email_settings['from_name']);
				$model_communication_newsletter['related_document_id'] = $lead['document_id'];
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
				
				/***************************************************
		         APPENDING VALUES IN URL 
		        ***************************************************/		
				$pq = new \xepan\cms\phpQuery();
				$dom = $pq->newDocument($email_body);

				foreach ($dom['a'] as $anchor){
					$a = $pq->pq($anchor);					
					$url = $this->app->url($a->attr('href'),['xepan_landing_contact_id'=>$lead->id,'xepan_landing_campaign_id'=>$lead['lead_campaing_id'],'xepan_landing_content_id'=>$lead['document_id'],'xepan_landing_emailsetting_id'=>$email_settings['id'],'source'=>'NewsLetter'])->absolute()->getURL();
					$a->attr('href',$url);
				}
				$email_body = $dom->html();

				$temp=$this->add('GiTemplate');
				$temp->loadTemplateFromString($email_body);
				$body_v=$this->add('View',null,null,$temp);
				$body_v->template->set($lead->get());

				$email_str = implode(',',$emails);

				
				$model_communication_newsletter['to_id'] =$lead->id;
				$model_communication_newsletter['related_id'] = $lead['schedule_id'];
				$model_communication_newsletter['related_document_id'] = $lead['document_id'];
				

				foreach ($emails as $email) {	
					$body_v->template->trySetHTML('unsubscribe','<a href='.$_SERVER["HTTP_HOST"].'/?page=xepan_marketing_unsubscribe&email_str='.$email.'&xepan_landing_contact_id='.$lead->id.'&schedule_id='.$lead['schedule_id'].'&document_id='.$lead['document_id'].'>Unsubscribe</>');
					$model_communication_newsletter->addTo($email);
					// var_dump($email);
				}

				$model_communication_newsletter->setSubject($subject_v->getHtml());
				$model_communication_newsletter->setBody($body_v->getHtml());

				if(!$this->debug){
					// throw new \Exception("DANGER - DEBUGING IS OFF");
					try {
						$model_communication_newsletter->send($email_settings, $mailer, $add_signatue=false);
						echo "Sent to ".$lead['name']." ".$lead['document']."<br/>";
					} catch (\Exception $e) {
						echo "Cant send to ".$lead['name']." ".$lead['document']." [".$e->getMessage()."] <br/>";

						// // still increase schedule_lead id to jump to next lead_id in next minute cron
						// $schedule_m = $this->add('xepan\marketing\Model_Schedule');
						// $schedule_m->load($lead['schedule_id']);
						// $schedule_m['last_communicated_lead_id'] = $lead->id;	
						// $schedule_m->save();
						// throw $e;
					}
					
				}else{

					$model_communication_newsletter->save();
					echo"**********************************************************************************<br/>";
					echo "name"." = ".$lead['name'] ."<br/>";
					echo "campaign"." = ".$lead['campaign_title'] ."<br/>";
					echo "campaign_type"." = ".$lead['campaign_type'] ."<br/>";
					echo "title"." = ".$lead['document'] ."<br/>";
					echo "schedule_date"." = ".$lead['schedule_date'] ."<br/>";
					echo "schedule_day"." = ".$lead['schedule_day'] ."<br/>";
					echo "sendable"." = ".$lead['sendable'] ."<br/>";
					echo "last_send_nwl"." = ".$lead['last_sent_newsletter_from_schedule_row_days'] ."<br/>";
					echo "last_send_nwl_date"." = ".$lead['last_sent_newsletter_date'] ."<br/>";
					echo "Body"." = ".$lead['body'] ."<br/>";
					echo"**********************************************************************************<br/><br/><br/>";
					
				}

				$schedule_m = $this->add('xepan\marketing\Model_Schedule');
				$schedule_m->load($lead['schedule_id']);
				$schedule_m['last_communicated_lead_id'] = $lead->id;	
				$schedule_m->save();	

				/***************************************************
			         check if we can continue with same email setting
			         or need next one with closing previous mailer 
			         and create new one : TODO
		        ***************************************************/

			    // if($loop_count >= $email_settings['smtp_auto_reconnect']){
			    // 	$mailer->disconnect();
			    // 	$mailer->connect();
			    // 	// echo "Reconnecting smtp connection <br/>";
			    // 	$loop_count=0;
			    // }

			    $done_contact_newsletter[]=$lead['id'].$lead['document_id'];

			    $loop_count++;

			}
		    echo "<br/>No Newsletters <br/>";

			// return $form->js()->univ()->successMessage('Newsletter Send')->execute();
		// }

	}
}