<?php

namespace xepan\marketing;

class Widget_LeadCount extends \xepan\base\Widget{
	function init(){
		parent::init();

		$this->report->enableFilterEntity('date_range');
		$this->report->enableFilterEntity('employee');

		$this->view = $this->add('View',null,null,['page\widget\panel']);
	}

	function recursiveRender(){
		$lead_created_m = $this->add('xepan\marketing\Model_Lead');

		if(isset($this->report->start_date))
			$lead_created_m->addCondition('created_at','>=',$this->report->start_date);
		else
			$lead_created_m->addCondition('created_at','>=',$this->app->today);

		if(isset($this->report->start_date))
			$lead_created_m->addCondition('created_at','<=',$this->app->nextDate($this->report->start_date));
		else
			$lead_created_m->addCondition('created_at','>=',$this->app->today);	

		$lead_assigned_m = clone($lead_created_m);
		$lead_assigned_m->addCondition('assign_to_id','<>',null);

		if(isset($this->report->employee)){
			$lead_created_m->addCondition('created_by_id',$this->report->employee);
			$lead_assigned_m->addCondition('assign_to_id',$this->report->employee);
		}

		$lead_created_count = $lead_created_m->count()->getOne();
		$lead_assigned_count = $lead_assigned_m->count()->getOne();

		$this->view->template->trySet('heading','Lead Created Count :: Lead Assigned Count');
		$this->view->template->trySet('value1',$lead_created_count);
		$this->view->template->trySet('value2',$lead_assigned_count);

		parent::recursiveRender();
	}
}