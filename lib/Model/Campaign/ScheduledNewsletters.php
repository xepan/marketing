<?php

namespace xepan\marketing;

class Model_Campaign_ScheduledNewsletters extends Model_Lead {

	public $pass_group_by = false;
	public $on_time = null;

	function init(){
		parent::init();

		$leads = $this;

		// DESTROYING UNNECESSERY FILEDS
		$leads->getElement('effective_name')->destroy();
		$leads->getElement('emails_str')->destroy();
		$leads->getElement('unique_name')->destroy();
		$leads->getElement('contacts_str')->destroy();
		$leads->getElement('contacts_comma_seperated')->destroy();
		$leads->getElement('scope')->destroy();

		$leads->getElement('created_by_id')->destroy();
		$leads->getElement('assign_to_id')->destroy();
		$leads->getElement('country')->destroy();
		$leads->getElement('state')->destroy();
		$leads->getElement('address')->destroy();
		$leads->getElement('city')->destroy();
		$leads->getElement('pin_code')->destroy();
		$leads->getElement('post')->destroy();
		$leads->getElement('website')->destroy();
		$leads->getElement('source')->destroy();
		$leads->getElement('remark')->destroy();
		$leads->getElement('freelancer_type')->destroy();
		$leads->getElement('image')->destroy();
		$leads->getElement('open_count')->destroy();
		$leads->getElement('converted_count')->destroy();
		$leads->getElement('rejected_count')->destroy();
		$leads->getElement('last_communication')->destroy();
		$leads->getElement('user')->destroy();
		// $leads->getElement('type')->destroy();
		$leads->getElement('last_communication_date_from_lead')->destroy();
		$leads->getElement('last_communication_date_from_company')->destroy();
		$leads->getElement('days_ago')->destroy();
		$leads->getElement('priority')->destroy();
		$leads->getElement('total_visitor')->destroy();

		// count active emails available
		$leads->addExpression('active_valid_emails_count')->set(function($m,$q){
			return $m->refSQL('Emails')->addCondition('is_active',true)->addCondition('is_valid',true)->count();
		});

		/***************************************************************************
			Joining tables to find lead->categories->campaigns->schedule->content
		***************************************************************************/
		
		$lead_cat_assos_j = $leads->join('lead_category_association.lead_id');
		$lead_cat_assos_j->addField('association_time','created_at');
		
		$camp_cat_assos_j = $lead_cat_assos_j->join('campaign_category_association.marketing_category_id','marketing_category_id');
		$camp_cat_assos_j->addField('campaign_cat_ass_time','created_at');
				
		$camp_j = $camp_cat_assos_j->join('campaign.document_id','campaign_id');
		$camp_j->addField('campaign_title','title');
		$camp_j->addField('campaign_type');
		$camp_j->addField('lead_campaing_id','document_id');
		$camp_document_j = $camp_j->join('document','document_id');
		$camp_document_j->addField('campaign_status','status');
		
		$schedule_j = $camp_j->join('schedule.campaign_id','document_id');
		$schedule_j->hasOne('xepan/marketing/Content','document_id','title');
		$schedule_j->addField('schedule_date','date');
		$schedule_j->addField('last_communicated_lead_id');
		$schedule_j->addField('schedule_day','day');
		$schedule_j->addField('schedule_id','id');
		$schedule_j->addField('schedule_content_id','document_id');
		
		$document_schecule_j = $schedule_j->join('document','document_id');
		$document_schecule_j->addField('document_type','type');
		
		// May be this is done by 'last_sent_newsletter_from_schedule_row_days' expression
		// $comm_j = $schedule_j->leftJoin('communication.related_id','document_id');
		// $comm_j->addField('communication_date','created_at');
		// $comm_j->addField('sent_to','to_id');


	// 	/***************************************************************************
	// 		Expression for finding total days since lead joined
	// 	***************************************************************************/
		$leads->addExpression('days_from_join')->set(function($m,$q){
			// NOW - (IF association_date > schedule_date then association_date else schedule_date)
			return $m->dsql()->expr("IF([lead_association_time] > [camp_association_time],DATEDIFF('[now]',[lead_association_time]),DATEDIFF('[now]',[camp_association_time]))",[
				'lead_association_time'=> $m->getElement('association_time'),
				'camp_association_time'=> $m->getElement('campaign_cat_ass_time'),
				'now'=>$this->api->today
				]);
		});

		/***************************************************************************
			Expression to find if the lead is 'Hot'/'sendable limit'
		***************************************************************************/
		
		$leads->addExpression('sendable')->set(function($m,$q){
			return $q->expr(
					"IF([campaign_type]='campaign',
						if([schedule_date]<='[now]',
						if([schedule_date]>=date([lead_association_time]),1,0),0),
						if([days_from_join]=[schedule_day],1,0)
						)",
					[
						'campaign_type'=> $m->getElement('campaign_type'),
						'schedule_date'=> $m->getElement('schedule_date'),
						'now' => $this->on_time?:$this->app->now,
						'days_from_join'=> $m->getElement('days_from_join'),
						'schedule_day'=> $m->getElement('schedule_day'),
						'lead_association_time'=> $m->getElement('association_time')
					]
					);
		})->type('boolean');

		$this->addExpression('is_already_sent')->set(function($m,$q){
			return $q->expr('[0] <= IFNULL([1],0)',[$m->getElement('id'),$m->getElement('last_communicated_lead_id')]);
		})->type('boolean');

		$this->setOrder('id');

	// 	/***************************************************************************
	// 		To find the last newsletter sending time.
	// 	***************************************************************************/	
		// $leads->addExpression('last_sent_newsletter_date')->set(function($m,$q){
		// 	return $this->add('xepan\marketing\Model_Communication_Newsletter')
		// 			->addCondition('related_id',$m->getElement('document_id'))
		// 			->addCondition('to_id',$m->getElement('id'))
		// 			->setOrder('created_at','desc')
		// 			->setLimit(1)
		// 			->fieldQuery('created_at');
		// });

		// $leads->addExpression('last_sent_newsletter_from_schedule_row_days')->set(function($m,$q){
		// 	return $q->expr("(DATEDIFF('[1]',IFNULL([0],'1970-01-01')))",
		// 		[
		// 		$m->getElement('last_sent_newsletter_date'),
		// 		$this->app->now
		// 		]);
		// })->caption('Last Newsletter Sent');

	// 	/***************************************************************************
	// 		Expression to extract 'message_3000' field from content model
	// 	***************************************************************************/
		$leads->addExpression('body')->set(function($m,$q){
			return $m->refSQL('document_id')->fieldQuery('message_blog');
		});

		$leads->addExpression('content_status')->set(function($m,$q){
			return $m->refSQL('document_id')->fieldQuery('status');
		});

		$leads->addCondition('active_valid_emails_count','>',0);
		
		if(!$this->pass_group_by){
			$leads->_dsql()->group(['lead_id',
									$leads->dsql()->expr('[0]',[$leads->getElement('schedule_id')])
									// $leads->dsql()->expr('[0]',[$leads->getElement('lead_campaing_id')])
								]);
		}

		
	}
}