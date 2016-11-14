<?php 

namespace xepan\marketing;

class Widget_OpportunityPipeline extends \xepan\base\Widget {
	
	function init(){
		parent::init();

		$this->report->enableFilterEntity('date_range');

		$this->chart = $this->add('xepan\base\View_Chart');
	}

	function recursiveRender(){
		$model = $this->add('xepan\marketing\Model_Opportunity');
		$model->addExpression('fund_sum')->set('sum(fund)');
		$model->_dsql()->group('status');
		$model->addCondition('created_at','>',$this->report->start_date);
		$model->addCondition('created_at','<',$this->app->nextDate($this->report->end_date));
		
		$this->chart->setType('pie')
	     			->setLabelToValue(true)
	    			->setModel($model,'status',['fund_sum'])
	    			->setTitle('Opportunities Pipeline');

		return parent::recursiveRender();
	}
}