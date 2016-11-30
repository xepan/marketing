<?php 

namespace xepan\marketing;

class Widget_GlobalMassCommunication extends \xepan\base\Widget {
	
	function init(){
		parent::init();

		$this->report->enableFilterEntity('date_range');

		$this->chart = $this->add('xepan\base\View_Chart');
	}

	function recursiveRender(){	
		$this->start_date = $this->report->start_date;
		$this->end_date = $this->app->nextDate($this->report->end_date);
		
		$model = $this->add('xepan\hr\Model_Employee');
			
		$model->addExpression('Newsletter')->set(function($m,$q){
			return $this->add('xepan\communication\Model_Communication')
						->addCondition('from_id',$q->getField('id'))
						->addCondition('communication_type','Newsletter')
						->addCondition('status','<>','Outbox')
						->addCondition('created_at','>',$this->start_date)
						->addCondition('created_at','<',$this->end_date)
						->count();
		});

		$model->addExpression('TeleMarketing')->set(function($m,$q){
			return $this->add('xepan\communication\Model_Communication')
						->addCondition('from_id',$q->getField('id'))
						->addCondition('communication_type','TeleMarketing')
						->addCondition('created_at','>',$this->start_date)
						->addCondition('created_at','<',$this->end_date)
						->count();
		});
		

		$model->addCondition([['TeleMarketing','>',0],['Newsletter','>',0]]);
		$model->addCondition('status','Active');

     	$this->chart->setType('bar')
     				->setModel($model,'name',['Newsletter','TeleMarketing'])
     				->setGroup(['Newsletter','TeleMarketing'])
 					->setTitle('Mass Communication')
     				->rotateAxis();

		return parent::recursiveRender();
	}
}




		




