<?php

namespace xepan\marketing;

class Widget_LeadPriority extends \xepan\base\Widget{
	function init(){
		parent::init();

		$this->grid = $this->add('xepan\hr\Grid');
	}

	function recursiveRender(){
		$lead_m = $this->add('xepan\marketing\Model_Lead');
		$lead_m->addCondition('score','>',0);
		$lead_m->setOrder('last_communication_date_from_company','desc');
		$lead_m->setOrder('last_communication_date_from_lead','desc');
		$lead_m->setOrder('last_landing_response_date_from_lead','desc');
		$lead_m->setOrder('score','desc');
		$lead_m->setOrder('priority','desc');
		$lead_m->setLimit(10);

		$this->grid->setModel($lead_m,['name','last_communication_date_from_company','days_ago','priority']);

		return parent::recursiveRender();
	}
}