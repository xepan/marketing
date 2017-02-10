<?php 

namespace xepan\marketing;

class Widget_LeadAndScore extends \xepan\base\Widget {
	
	function init(){
		parent::init();

		$this->report->enableFilterEntity('date_range');

		$this->chart = $this->add('xepan\base\View_Chart');
	}

	function recursiveRender(){
		$model = $this->add('xepan\marketing\Model_Lead');
		$model->addCondition('status','Active');
		$model->addExpression('lead_count')->set('count(*)');
		$model->addExpression('score_sum')->set(function($m,$q){
			return $q->expr('IFNULL([0],0)',[$this->add('xepan\base\Model_PointSystem')->addCondition('contact_id',$q->getField('id'))->sum('score')]);
		});

		$model->addExpression('Date','DATE(created_at)');
		$model->addExpression('Month','DATE_FORMAT(created_at,"%Y %M")');
		$model->addExpression('Year','YEAR(created_at)');
		$model->addExpression('Week','WEEK(created_at)');

		$model->_dsql()->group('Date');
		$model->addCondition('created_at','>',$this->report->start_date);
		$model->addCondition('created_at','<',$this->app->nextDate($this->report->end_date));

		$this->chart->setType('line')
	    			->setModel($model,'Date',['lead_count','score_sum'])
	    			->setTitle('Lead Count Vs Score');
		
		return parent::recursiveRender();
	}
}