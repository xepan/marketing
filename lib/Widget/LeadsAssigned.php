<?php 

namespace xepan\marketing;

class Widget_LeadsAssigned extends \xepan\base\Widget {
	
	function init(){
		parent::init();

		$this->report->enableFilterEntity('date_range');
		$this->report->enableFilterEntity('Employee');
		$this->grid = $this->add('xepan\hr\Grid',null,null,['page\widget\leads-assigned']);
		$this->grid->add('View',null,'grid_buttons')->setHtml('<b>Lead Assigned</b>');
		$this->grid->removeSearchIcon();
	}

	function recursiveRender(){
		$lead_m = $this->add('xepan\marketing\Model_Lead');
		$lead_m->addCondition('assign_to_id','<>',null);

		if($this->report->start_date)
			$lead_m->addCondition('created_at','>=',$this->report->start_date);
		else
			$lead_m->addCondition('created_at','>=',$this->app->today);

		if($this->report->end_date)
			$lead_m->addCondition('created_at','<=',$this->app->nextDate($this->report->end_date));
		else
			$lead_m->addCondition('created_at','<=',$this->app->nextDate($this->app->today));

		if(isset($this->report->employee))
			$lead_m->addCondition('assign_to_id',$this->report->employee);

		$this->grid->setModel($lead_m,['name','created_by','assign_to','source']);
		$this->grid->addPaginator(10);

		$this->grid->js('click')->_selector('.do-view-lead')->univ()->frameURL('Lead Details',[$this->api->url('xepan_marketing_leaddetails'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);

		return parent::recursiveRender();
	}
}