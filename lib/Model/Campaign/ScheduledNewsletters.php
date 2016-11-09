<?php


namespace xepan\marketing;

class Model_Campaign_ScheduledNewsletters extends Model_Lead {
	function init(){
		parent::init();

		$leads = $this;

		$leads->getElement('total_visitor')->destroy();
		$leads->getElement('score')->destroy();

		/***************************************************************************
			Joining tables to find lead->categories->campaigns->schedule->content
		***************************************************************************/
		
		$lead_cat_assos_j = $leads->join('lead_category_association.lead_id');
		$lead_cat_assos_j->addField('association_time','created_at');
		
		$camp_cat_assos_j = $lead_cat_assos_j->join('campaign_category_association.marketing_category_id','marketing_category_id');
				
		$camp_j = $camp_cat_assos_j->join('campaign.document_id','campaign_id');
		$camp_j->addField('campaign_title','title');
		$camp_j->addField('campaign_type');
		$camp_j->addField('lead_campaing_id','document_id');
		$camp_document_j = $camp_j->join('document','document_id');
		$camp_document_j->addField('campaign_status','status');
		
		$schedule_j = $camp_j->join('schedule.campaign_id','document_id');
		$schedule_j->hasOne('xepan/marketing/Content','document_id','title');
		$schedule_j->addField('schedule_date','date');
		$schedule_j->addField('schedule_day','day');
		$schedule_j->addField('schedule_id','id');
		
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
			return $m->dsql()->expr("DATEDIFF('[1]',[0])",[$m->getElement('created_at'),$this->api->today]);
		});

		/***************************************************************************
			Expression to find if the lead is 'Hot'/'sendable limit'
		***************************************************************************/
		
		$leads->addExpression('sendable')->set(function($m,$q){
			return $q->expr(
					"IF([campaign_type]='campaign',
						if([schedule_date]<='[now]',
						if([schedule_date]>[lead_association_time],1,0),0),
						if([days_from_join]>=[schedule_day],1,0)
						)",
					[
						'campaign_type'=> $m->getElement('campaign_type'),
						'schedule_date'=> $m->getElement('schedule_date'),
						'now' => $this->app->now,
						'days_from_join'=> $m->getElement('days_from_join'),
						'schedule_day'=> $m->getElement('schedule_day'),
						'lead_association_time'=> $m->getElement('association_time')
					]
					);
		})->type('boolean');

		$this->addExpression('is_already_sent')->set(function($m,$q){
			$comm_m = $this->add('xepan\communication\Model_Communication');
			$comm_m->addCondition('related_id',$m->getElement('schedule_id'));
			$comm_m->addCondition('to_id',$m->getElement('id'));
			$comm_m->addCondition('related_document_id',$m->getElement('document_id'));
			return $comm_m->count();		
		})->type('boolean');

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


		
	}
}