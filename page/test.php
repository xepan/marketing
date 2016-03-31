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
		
		// campaign 
		// 		-< schedule
		// 			-> content_id *
		// 			->date
		// 			->day
		// 		-< campaign category_association
		// 			-< Lead / Contact (emails_str) [-< Emails * ]

		$lead = $this->add('xepan\marketing\Model_Lead');
		
		$lead_cat_assos_j = $lead->join('lead_category_association.lead_id');
		$camp_cat_assos_j = $lead_cat_assos_j->join('campaign_category_association.marketing_category_id','marketing_category_id');
		$camp_j = $camp_cat_assos_j->join('campaign','campaign_id');
		$schedule_j = $camp_j->join('schedule.campaign_id');
		$schedule_j->hasOne('xepan/marketing/Content','document_id');
		$comm_j = $schedule_j->leftJoin('communication.related_id','document_id');
		
		$comm_j->addField('related_id');
		$schedule_j->addField('date');

		$lead->addCondition('related_id',null);
		$lead->addCondition('date','<=',$this->app->now);

		$grid= $this->add('Grid');
		$grid->setModel($lead->debug(),['name','document']);

	}
}