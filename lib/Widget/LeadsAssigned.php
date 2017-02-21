<?php 

namespace xepan\marketing;

class Widget_LeadsAssigned extends \xepan\base\Widget {
	
	function init(){
		parent::init();

		$this->report->enableFilterEntity('date_range');
		$this->report->enableFilterEntity('employee');
		$this->grid = $this->add('xepan\hr\Grid');
		$this->grid->add('View',null,'grid_buttons')->setHtml('<b>Lead Assigned</b>');
		$this->grid->removeSearchIcon();
	}

	function recursiveRender(){
		$lead_m = $this->add('xepan\marketing\Model_Lead');
		$lead_m->addCondition('created_at','>=',$this->report->start_date);
		$lead_m->addCondition('created_at','<=',$this->app->nextDate($this->report->end_date));
		
		if(isset($this->report->employee))
			$lead_m->addCondition('assign_to_id',$this->report->employee);

		$this->grid->setModel($lead_m,['name','created_by','assign_to','source']);

		return parent::recursiveRender();
	}
}