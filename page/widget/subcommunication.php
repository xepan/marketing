<?php

namespace xepan\marketing;

class page_widget_subcommunication extends \xepan\base\Page{
	function init(){
		parent::init();

		$x_axis = $this->app->stickyGET('x_axis');
		$details = $this->app->stickyGET('details');
		$details = json_decode($details,true);
		$start_date = $this->app->stickyGET('start_date')?:$this->app->today;
		$end_date = $this->app->stickyGET('end_date')?:$this->app->today;

		$model_employee = $this->add('xepan\hr\Model_Employee');
		$model_employee->tryLoadBy('name',$x_axis);
		
		if(!$model_employee->loaded() AND $_GET['employee'])
			$model_employee->load($_GET['employee']);
		
		$contact_id	 = $model_employee->id;	

		$type = $details['name'];
		
		$view_conversation = $this->add('xepan\communication\View_Lister_Communication',['contact_id'=>$contact_id]);

		$model_communication = $this->add('xepan\communication\Model_Communication');
		$model_communication->addCondition('created_at','>=',$start_date);
		$model_communication->addCondition('created_at','<=',$this->app->nextDate($end_date));
		$model_communication->addCondition([['from_id',$contact_id],['to_id',$contact_id]]);
		$model_communication->addCondition('sub_type',$type);
		
		$view_conversation->setModel($model_communication)->setOrder('created_at','desc');
		$view_conversation->add('Paginator',['ipp'=>10]);
	}
}