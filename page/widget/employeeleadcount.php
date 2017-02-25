<?php

namespace xepan\marketing;

class page_widget_employeeleadcount extends \xepan\base\Page{
	function init(){
		parent::init();
		
		$department_id = $this->app->stickyGET('department_id');
		$start_date = $this->app->stickyGET('start_date');
		$end_date = $this->app->stickyGET('end_date');

		$model_employee = $this->add('xepan\hr\Model_Employee');
		$model_employee->addCondition('status','Active');
		$model_employee->addCondition('department_id',$_GET['department_id']);		
		
		$model_employee->addExpression('lead_count')->set(function($m,$q)use($start_date,$end_date){			
			return $this->add('xepan\marketing\Model_Lead',['table_alias'=>'lead_count'])
						->addCondition('created_at','>=',$start_date)
						->addCondition('created_at','<=',$this->app->nextDate($end_date))
						->addCondition('created_by_id',$q->getField('id'))
						->count();
		});
				
		$grid = $this->add('xepan\hr\Grid',null,null,['page\widget\employee-leadcount']);
		$grid->setModel($model_employee,['name','lead_count']);
	
		$grid->js('click')->_selector('.do-view-employeeleadcount')->univ()->frameURL('Leads Added',[$this->api->url('xepan_marketing_widget_leadsadded'),['start_date'=>$start_date,'end_date'=>$end_date],'employee_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
	}
}