<?php 

namespace xepan\marketing;

class Widget_SaleStaffStatus extends \xepan\base\Widget {
	
	function init(){
		parent::init();

		$this->report->enableFilterEntity('date_range');
		$this->report->enableFilterEntity('Employee');

		$this->chart = $this->add('xepan\base\View_Chart');
	}

	function recursiveRender(){	
		$this->start_date = $this->report->start_date;
		$this->end_date = $this->app->nextDate($this->report->end_date);
		
		$model = $this->add('xepan\hr\Model_Employee');
	    
		if(isset($this->report->employee))
			$model->addCondition('id',$this->report->employee);

	    $model->hasMany('xepan\marketing\Opportunity','assign_to_id',null,'Oppertunities');
		$model->addExpression('Open')->set($model->refSQL('Oppertunities')->addCondition('status','Open')->addCondition('created_at','>',$this->start_date)->addCondition('created_at','<',$this->end_date)->sum('fund'));
		$model->addExpression('Qualified')->set($model->refSQL('Oppertunities')->addCondition('status','Qualified')->addCondition('created_at','>',$this->start_date)->addCondition('created_at','<',$this->end_date)->sum('fund'));
		$model->addExpression('NeedsAnalysis')->set($model->refSQL('Oppertunities')->addCondition('status','NeedsAnalysis')->addCondition('created_at','>',$this->start_date)->addCondition('created_at','<',$this->end_date)->sum('fund'));
		$model->addExpression('Quoted')->set($model->refSQL('Oppertunities')->addCondition('status','Quoted')->addCondition('created_at','>',$this->start_date)->addCondition('created_at','<',$this->end_date)->sum('fund'));
		$model->addExpression('Negotiated')->set($model->refSQL('Oppertunities')->addCondition('status','Negotiated')->addCondition('created_at','>',$this->start_date)->addCondition('created_at','<',$this->end_date)->sum('fund'));
		$model->addExpression('Won')->set($model->refSQL('Oppertunities')->addCondition('status','Won')->addCondition('created_at','>',$this->start_date)->addCondition('created_at','<',$this->end_date)->sum('fund'));
		$model->addExpression('Lost')->set($model->refSQL('Oppertunities')->addCondition('status','Lost')->addCondition('created_at','>',$this->start_date)->addCondition('created_at','<',$this->end_date)->sum('fund'));
		$model->addCondition([['Open','>',0],['Qualified','>',0],['NeedsAnalysis','>',0],['Quoted','>',0],['Negotiated','>',0]]);
		$model->addCondition('status','Active');

 		$this->chart->setType('bar')
		     		->setModel($model,'name',['Open','Qualified','NeedsAnalysis','Quoted','Negotiated'])
		     		->setGroup(['Open','Qualified','NeedsAnalysis','Quoted','Negotiated'])
		     		->setTitle('Sales Staff Status')
		     		->rotateAxis()
		     		->openOnClick('xepan_marketing_widget_salesstaffstatus');

		return parent::recursiveRender();
	}
}




