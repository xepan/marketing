<?php

namespace xepan\marketing;

class Widget_LeadCount extends \xepan\base\Widget{
	function init(){
		parent::init();

		$this->report->enableFilterEntity('date_range');
		$this->report->enableFilterEntity('Employee');

		$this->view = $this->add('View',null,null,['page\widget\panel']);
	}

	function recursiveRender(){
		$start_date = isset($this->report->start_date)?$this->report->start_date:$this->app->today;
		$end_date =  isset($this->report->end_date)?$this->report->end_date:$this->app->today;
		$employee_id =  isset($this->report->employee)?$this->report->employee:$this->app->employee->id;
		
		$employee_m = $this->add('xepan\hr\Model_Employee')->load($employee_id);
		
		$lead_created_m = $this->add('xepan\marketing\Model_Lead');
		$lead_assigned_m = $this->add('xepan\marketing\Model_Lead');

		$lead_created_m->addCondition('created_at','>=',$start_date);
		$lead_created_m->addCondition('created_at','<=',$this->app->nextDate($end_date));
		$lead_created_m->addCondition('created_by_id',$employee_id);

		$lead_assigned_m->addCondition('created_at','>=',$start_date);
		$lead_assigned_m->addCondition('created_at','<=',$this->app->nextDate($end_date));
		$lead_assigned_m->addCondition('assign_to_id','<>',null);
		$lead_assigned_m->addCondition('assign_to_id',$employee_id);

		$lead_created_count = $lead_created_m->count()->getOne();
		$lead_assigned_count = $lead_assigned_m->count()->getOne();

		$this->view->template->trySet('employee',$employee_m['name']);
		$this->view->template->trySet('heading','Lead Count');
		$this->view->template->trySet('value1',$lead_created_count);
		$this->view->template->trySet('value2',$lead_assigned_count);

		$this->view->js('click')->_selector('.value1')->univ()->frameURL('Leads Added',[$this->api->url('xepan_marketing_widget_leadsadded'),['start_date'=>$start_date,'end_date'=>$end_date,'employee_id'=>$employee_id]]);
		$this->view->js('click')->_selector('.value2')->univ()->frameURL('Leads Assigned',[$this->api->url('xepan_marketing_widget_leadsassigned'),['start_date'=>$start_date,'end_date'=>$end_date,'employee_id'=>$employee_id]]);
		
		parent::recursiveRender();
	}
}