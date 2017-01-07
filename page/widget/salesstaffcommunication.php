<?php

namespace xepan\marketing;

class page_widget_salesstaffcommunication extends \xepan\base\Page{
	function init(){
		parent::init();

		$x_axis = $this->app->stickyGet('x_axis');
		$details = $this->app->stickyGet('details');
		$details = json_decode($details,true);
		
		$start_date = $this->app->stickyGet('start_date')?:$this->app->today;
		$end_date = $this->app->stickyGet('end_date')?:$this->app->today;

		$model_employee = $this->add('xepan\hr\Model_Employee');
		$model_employee->loadBy('name',$x_axis);
		$contact_id	 = $model_employee->id;	

		$type = $details['name'];
		
		$view_conversation = $this->add('xepan\communication\View_Lister_Communication',['contact_id'=>$contact_id]);

		$model_communication = $this->add('xepan\communication\Model_Communication');
		$model_communication->addCondition('created_at','>',$start_date);
		$model_communication->addCondition('created_at','<',$this->app->nextDate($end_date));
		$model_communication->addCondition([['from_id',$contact_id],['to_id',$contact_id]]);
		$model_communication;
		if($type =='Meeting')
			$model_communication->addCondition('communication_type','Personal');
		else
			$model_communication->addCondition('communication_type',$type);
		
		$view_conversation->setModel($model_communication)->setOrder('created_at','desc');
		$view_conversation->add('Paginator',['ipp'=>10]);
	}
}