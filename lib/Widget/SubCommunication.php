<?php

namespace xepan\marketing;

class Widget_SubCommunication extends \xepan\base\Widget{
	
	function init(){
		parent::init();
		
		$this->report->enableFilterEntity('date_range');
		$this->report->enableFilterEntity('Employee');
		$this->chart = $this->add('xepan\base\View_Chart');
	}

	function recursiveRender(){
		$this->start_date = isset($this->report->start_date)?$this->report->start_date:$this->app->today;
		$this->end_date =  isset($this->report->end_date)?$this->app->nextDate($this->report->end_date):$this->app->nextDate($this->app->today);
		
		$config_m = $this->add('xepan\base\Model_ConfigJsonModel',
		[
			'fields'=>[
						'sub_type'=>'text',
						],
				'config_key'=>'COMMUNICATION_SUB_TYPE',
				'application'=>'Communication'
		]);
		$config_m->tryLoadAny();

		$sub_type = explode(',', $config_m['sub_type']);
		
		$model = $this->add('xepan\hr\Model_Employee');
		$model->addCondition('status','Active');
		
		if(isset($this->report->employee))
			$model->addCondition('id',$this->report->employee);

		foreach ($sub_type as $st){			
			$model->addExpression($st)->set(function($m,$q)use($st){
				return $this->add('xepan\communication\Model_Communication')
							->addCondition('from_id',$q->getField('id'))
							->addCondition('sub_type',$st)
							->addCondition('status','<>','Outbox')
							->addCondition('created_at','>',$this->start_date)
							->addCondition('created_at','<',$this->end_date)
							->count();
			});
		}
					
     	$this->chart->setType('bar')
     				->setModel($model,'name',$sub_type)
     				->setGroup($sub_type)
 					->setTitle('Sub Communication')
     				->rotateAxis()
     				->openOnClick('xepan_marketing_widget_subcommunication');

		return parent::recursiveRender();

	}
}