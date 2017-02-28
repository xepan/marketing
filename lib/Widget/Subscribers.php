<?php

namespace xepan\marketing;

class Widget_Subscribers extends \xepan\base\Widget{
	function init(){
		parent::init();

		$this->report->enableFilterEntity('date_range');
		$this->view = $this->add('View',null,null,['page\widget\subscription']);
	}

	function recursiveRender(){
		$start_date = isset($this->report->start_date)?$this->report->start_date:$this->app->today;
		$end_date =  isset($this->report->end_date)?$this->report->end_date:$this->app->today;

		$lead_m = $this->add('xepan\marketing\Model_Lead');
		$lead_m->addCondition('source','Subscription');
		$lead_m->addCondition('created_at','>=',$start_date);
		$lead_m->addCondition('created_at','<=',$this->app->nextDate($end_date));

		$this->view->template->trySet('daterange',$start_date." To ".$end_date);
		$this->view->template->trySet('leadcount',$lead_m->count());

		$this->view->js('click')->_selector('.do-view-onlinesubscribers')->univ()->frameURL('Online Subscribers',$this->app->url('xepan_marketing_lead',['start_date'=>$start_date,'end_date'=>$end_date,'source'=>'Subscription']));
				
		return parent::recursiveRender();
	}
}