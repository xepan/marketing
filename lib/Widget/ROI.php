<?php 

namespace xepan\marketing;

class Widget_ROI extends \xepan\base\Widget {
	
	function init(){
		parent::init();

		$this->report->enableFilterEntity('date_range');
		$this->report->enableFilterEntity('Employee');

		$this->chart = $this->add('xepan\base\View_Chart');
	}

	function recursiveRender(){
		$model = $this->add('xepan\marketing\Model_Opportunity');
		$model->addExpression('fund_sum')->set('sum(fund)');
		$model->addExpression('source_filled')->set($model->dsql()->expr('IFNULL([0],"unknown")',[$model->getElement('source')]));
		$model->addCondition('status','Won');
		$model->_dsql()->group('source_filled');
		$model->addCondition('created_at','>',$this->report->start_date);
		$model->addCondition('created_at','<',$this->app->nextDate($this->report->end_date));
		
		if(isset($this->report->employee))
			$model->addCondition([['created_by_id',$this->report->employee],['assign_to_id',$this->report->employee]]);

		$this->chart->setType('pie')
	    			->setModel($model,'source_filled',['fund_sum'])
	    			->setTitle('Won Business Sources')
	    			->openOnClick('xepan_marketing_widget_roi');

		return parent::recursiveRender();
	}
}