<?php 

namespace xepan\marketing;

class Widget_DayByDayCommunication extends \xepan\base\Widget {
	
	function init(){
		parent::init();

		$this->report->enableFilterEntity('date_range');
		$this->report->enableFilterEntity('Employee');
	}

	function recursiveRender(){
		$active_emp = $this->app->employee->getActiveEmployeeIds();

		$communication_graph = $this->add('xepan\communication\Model_Communication');
		$communication_graph->addExpression('date','date(created_at)');
		$communication_graph->addExpression('score','count(*)');

		if(isset($this->report->employee))
			$communication_graph->addCondition([['from_id',$this->report->employee],['to_id',$this->report->employee]]);
		else{
			$communication_graph->addCondition([['from_id',$active_emp],['to_id',$active_emp]]);
		}

		if(isset($this->report->start_date))
			$communication_graph->addCondition('created_at','>',$this->report->start_date);
		if(isset($this->report->end_date))
			$communication_graph->addCondition('created_at','<',$this->app->nextDate($this->report->end_date));
		
		$communication_graph->addCondition('status','<>','Outbox');
		$communication_graph->setOrder('date','asc')
							->_dsql()->group(['communication_type',$communication_graph->_dsql()->expr('[0]',[$communication_graph->getElement('date')])]);
		
		$data_array = [];
		foreach ($communication_graph as $model) {
			if(!isset($data_array[$model['date']])) $data_array[$model['date']]=[];
			$data_array[$model['date']] = array_merge($data_array[$model['date']],['date'=>$model['date'], $model['communication_type']=>$model['score']]);
		}

		$data_array = array_values($data_array);

		$this->chart = $this->add('xepan\base\View_Chart')
	 		->setType('bar')
	 		->setData(['json'=>$data_array])
	 		->setGroup(['Email','Newsletter','Call','Personal','Comment','TeleMarketing'])
	 		->setXAxis('date')
	 		->setYAxises(['Email','Newsletter','Call','Personal','Comment','TeleMarketing'])
	 		->addClass('col-md-12')
	 		->setTitle('Communication');
	 		
		return parent::recursiveRender();
	}
}