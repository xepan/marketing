<?php


namespace xepan\marketing;

class View_TeleGraph extends \View{
	function init(){
		parent::init();	

		$model_telecomm = $this->add('xepan\marketing\Model_TeleCommunication');
		
		$model_telecomm->addExpression('daily_analysis')->set(function($m,$q){
			return "''";
		});

		$model_telecomm->addExpression('weekly_analysis')->set(function($m,$q){
			return "''";
		});

		$model_telecomm->addExpression('monthly_analysis')->set(function($m,$q){
			return "''";
		});		

		
		$this->js(true)->_load('jquery.sparkline.min');			
		
		$this->js(true)->_selector('.sparkline.daily_graph')->sparkline();
		$this->js(true)->_selector('.sparkline.weekly_graph')->sparkline();
		$this->js(true)->_selector('.sparkline.monthly_graph')->sparkline();
	}

	function defaultTemplate(){
		return ['view\telegraph'];
	}
}