<?php

namespace xepan\marketing;

class View_BarChart extends \View{
	function init(){
		parent::init();

		$chart = $this->add('xepan\base\View_Chart');
		$chart->setChartType("Area");
		$chart->setLibrary("Morris");
		$chart->setXAxis('x');
		$chart->setYAxis(['y', 'z']);
		$data = [
				['x'=> '2011 Q1', 'y'=> 3, 'z'=> 3],
				['x'=> '2011 Q2', 'y'=> 2, 'z'=> 1],
				['x'=> '2011 Q3', 'y'=> 2, 'z'=> 4],
				['x'=> '2011 Q4', 'y'=> 3, 'z'=> 3]
			];
		$chart->setData($data);
		$chart->setOption('behaveLikeLine',true);
		$chart->setLabels(['Y', 'Z']);
	}

	function render(){
		return parent::render();
	}
	// function defaultTemplate(){
	// 	return ['view/barchart'];
	// }
}