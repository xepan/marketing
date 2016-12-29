<?php

namespace xepan\marketing;

class page_scheduletimeline extends \xepan\base\Page{
	public $title = "Schedule Timeline";

	function init(){
		parent::init();

		$leads = $this->add('xepan\marketing\Model_Campaign_ScheduledNewsletters');
		$leads->addCondition('sendable',true);
		$leads->addCondition('campaign_status','Approved');
		$leads->addCondition('content_status','Approved');
		$leads->addCondition('is_already_sent',0);
		$leads->addCondition('document_type','Newsletter');
		
		$grid = $this->add('xepan\hr\Grid');
		$grid->setModel($leads,['name','emails_str','unique_name','campaign_title','campaign_type','document']);
		$grid->addPaginator(50);
		$form = $grid->addQuickSearch(['lead_campaing_id','name','emails_str','unique_name','campaign_title','document']);
		
		$campaign_m = $this->add('xepan\marketing\Model_Campaign');
		$campaign_filter = $form->addField('DropDown','campaign');
		$campaign_filter->setModel($campaign_m);
		$campaign_filter->setEmptyText('Please select a campaign');

		$form->addHook('applyFilter',function($f,$m){
			if($f['campaign']){
				$m->addCondition('lead_campaing_id',$f['campaign']);
			}
		});

		$campaign_filter->js('change',$form->js()->submit());
	}
}