<?php

namespace xepan\marketing;

class Widget_LeadsAdded extends \xepan\base\Widget{
	function init(){
		parent::init();

		$this->report->enableFilterEntity('date_range');
		$this->report->enableFilterEntity('Employee');

		$this->grid = $this->add('xepan\hr\Grid',null,null,['page\widget\leads-added']);
		$this->grid->add('View',null,'grid_buttons')->setHtml('<b>Leads Added</b>');
		$this->grid->removeSearchIcon();
	}

	function recursiveRender(){
		$lead_m = $this->add('xepan\marketing\Model_Lead');
		
		if(isset($this->report->start_date))
			$lead_m->addCondition('created_at','>=',$this->report->start_date);
		else
			$lead_m->addCondition('created_at','>=',$this->app->today);

		if(isset($this->report->end_date))
			$lead_m->addCondition('created_at','<=',$this->app->nextDate($this->report->end_date));
		else
			$lead_m->addCondition('created_at','<=',$this->app->nextDate($this->app->today));

		if(isset($this->report->employee))
			$lead_m->addCondition('created_by_id',$this->report->employee);

		$this->grid->setModel($lead_m,['name','type','created_at']);
		$this->grid->addPaginator(10);

		parent::recursiveRender();
	}
}