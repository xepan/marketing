<?php

namespace xepan\marketing;

class page_widget_leadsassigned extends \xepan\base\Page{
	function init(){
		parent::init();

		$start_date = $this->app->stickyGET('start_date');
		$end_date = $this->app->stickyGET('end_date');
		$employee_id = $this->app->stickyGET('employee_id');

		$lead_m = $this->add('xepan\marketing\Model_Lead');
		$lead_m->addCondition('created_at','>=',$start_date);
		$lead_m->addCondition('created_at','<=',$this->app->nextDate($end_date));
		$lead_m->addCondition('assign_to_id',$employee_id);

		$grid = $this->add('xepan\hr\Grid',null,null,['page\widget\leads-assigned']);
		$grid->add('View',null,'grid_buttons')->setHtml('<b>Leads Assigned</b>');
		$grid->removeSearchIcon();
		$grid->setModel($lead_m,['name','created_at','created_by','assign_to']);
		$grid->addPaginator(10);

		$grid->js('click')->_selector('.do-view-lead')->univ()->frameURL('Lead Details',[$this->api->url('xepan_marketing_leaddetails'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
	}
}