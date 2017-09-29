<?php

namespace xepan\marketing;

class Widget_DepartmentLeadsAdded extends \xepan\base\Widget{
	function init(){
		parent::init();
		$this->report->enableFilterEntity('date_range');
		$this->report->enableFilterEntity('Department');
		
		$this->grid = $this->add('xepan\hr\Grid',null,null,['page\widget\department-lead']);
		$this->grid->add('View',null,'grid_buttons')->setHtml('<b>Lead Created</b>');
		$this->grid->removeSearchIcon();
	}

	function recursiveRender(){
		$start_date = isset($this->report->start_date)?$this->report->start_date:$this->app->today;
		$end_date =  isset($this->report->end_date)?$this->report->end_date:$this->app->today;

		$department_m = $this->add('xepan\hr\Model_Department');
		
		if(isset($this->report->department))
			$department_m->addCondition('id',$this->report->department);

		$department_m->addExpression('lead_count')->set(function($m,$q) use($start_date,$end_date){
			$emp_m = $this->add('xepan\hr\Model_Employee');
			$emp_lead_j = $emp_m->join('contact.created_by_id','id');
			$emp_lead_j->addField('lead_created_at','created_at');
			
			$emp_m->addCondition('department_id',$m->getElement('id'));
			$emp_m->addCondition('lead_created_at','>=',$start_date);
			$emp_m->addCondition('lead_created_at','<=',$this->app->nextDate($end_date));
			return $emp_m->count();
		});

		$this->grid->setModel($department_m,['name','lead_count']);

		$this->grid->js('click')->_selector('.do-view-employee')->univ()->frameURL('Employee Lead Count',[$this->api->url('xepan_marketing_widget_employeeleadcount'),['start_date'=>$start_date,'end_date'=>$end_date],'department_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);

		return parent::recursiveRender();
	}
}