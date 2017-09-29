<?php 

namespace xepan\marketing;

class Widget_SaleStaffCommunication extends \xepan\base\Widget {
	
	function init(){
		parent::init();

		$this->report->enableFilterEntity('date_range');
		$this->report->enableFilterEntity('Employee');
		if($this->isChart())
			$this->chart = $this->add('xepan\base\View_Chart');
		
	}

	function recursiveRender(){	
		$this->start_date = $this->report->start_date;
		$this->end_date = $this->app->nextDate($this->report->end_date);
		
		$model = $this->add('xepan\hr\Model_Employee');
		
		if(isset($this->report->employee))
			$model->addCondition('id',$this->report->employee);

		$model->addExpression('Email')->set(function($m,$q){
			return $this->add('xepan\communication\Model_Communication')
						->addCondition([['from_id',$q->getField('id')],['to_id',$q->getField('id')]])
						->addCondition('communication_type','Email')
						->addCondition('status','<>','Outbox')
						->addCondition('created_at','>',$this->start_date)
						->addCondition('created_at','<',$this->end_date)
						->count();
		});
		
		$model->addExpression('Call')->set(function($m,$q){
			return $this->add('xepan\communication\Model_Communication')
						->addCondition([['from_id',$q->getField('id')],['to_id',$q->getField('id')]])
						->addCondition('communication_type','Call')
						->addCondition('created_at','>',$this->start_date)
						->addCondition('created_at','<',$this->end_date)
						->count();
		});

		$model->addExpression('Meeting')->set(function($m,$q){
			return $this->add('xepan\communication\Model_Communication')
						->addCondition([['from_id',$q->getField('id')],['to_id',$q->getField('id')]])
						->addCondition('communication_type','Personal')
						->addCondition('created_at','>',$this->start_date)
						->addCondition('created_at','<',$this->end_date)
						->count();
		});

		$model->addCondition([['Email','>',0],['Call','>',0],['Meeting','>',0]]);
		$model->addCondition('status','Active');

		if($this->isChart()){

	     	$this->chart->setType('bar')
	     				->setModel($model,'name',['Email','Call','Meeting'])
	     				->setGroup(['Email','Call','Meeting'])
	 					->setTitle('Sales Staff Communication')
	     				->rotateAxis()
	     				->openOnClick('xepan_marketing_widget_salesstaffcommunication');
		}else{
			$this->add('Grid')->setModel($model,['name','Email']);
		}


		return parent::recursiveRender();
	}
}




		




