<?php

namespace xepan\marketing;

/**
* 
*/
class page_report_employeeleadreport extends \xepan\base\Page{

	public $title = "Employee Lead Report`s";
	function init(){
		parent::init();
		$emp_id = $this->app->stickyGET('employee_id');
		$from_date = $this->app->stickyGET('from_date');
		$to_date = $this->app->stickyGET('to_date');
		$form = $this->add('Form',null,null,['form/empty']);
		$date = $form->addField('DateRangePicker','date_range');
		$set_date = $this->app->today." to ".$this->app->today;
		if($from_date){
			$set_date = $from_date." to ".$to_date;
			$date->set($set_date);	
		}
		$emp_field = $form->addField('xepan\base\Basic','employee');
		$emp_field->setModel('xepan\hr\Model_Employee')->addCondition('status','Active');
		
		$emp_model = $this->add('xepan\marketing\Model_EmployeeLead',['from_date'=>$from_date,'to_date'=>$to_date]);
		if($emp_id){
			$emp_model->addCondition('id',$emp_id);
		}
		if($_GET['from_date']){
			$emp_model->from_date = $_GET['from_date'];
		}
		if($_GET['from_date']){
			$emp_model->to_date = $_GET['to_date'];
		}
		$form->addSubmit('Get Details')->addClass('btn btn-primary');

		$grid = $this->add('xepan\hr\Grid',null,null,['view/report/employee-lead-report-gridview']);
		$grid->setModel($emp_model);
		$grid->addPaginator(50);

		if($form->isSubmitted()){

			$grid->js()->reload(
							[
								'employee_id'=>$form['employee'],
								'from_date'=>$date->getStartDate()?:0,
								'to_date'=>$date->getEndDate()?:0
							]
				)->execute();
			
			// $form->js()->univ()->redirect($this->app->url(),[
			// 					'employee_id'=>$form['employee'],
			// 					'from_date'=>$date->getStartDate()?:0,
			// 					'to_date'=>$date->getEndDate()?:0
			// 				]
							
			// 			)->execute();
		}
	}
}