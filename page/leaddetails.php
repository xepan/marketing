<?php

namespace xepan\marketing;

class page_leaddetails extends \Page {
	public $title ='Lead Details';

	function init(){
		parent::init();

		$lead= $this->add('xepan\marketing\Model_Lead')->tryLoadBy('id',$this->api->stickyGET('contact_id'));
		
		$lead_view = $this->add('xepan\base\View_Contact',null,'contact_view');
		$lead_view->setModel($lead);

		$details_view = $this->add('xepan\marketing\View_details',null,'details');
		$activity_view = $this->add('xepan\marketing\View_activity',null,'activity');
		$opportunity_view = $this->add('xepan\marketing\View_opportunity',null,'opportunity');
		$lead_view->setModel($lead);
	}

	function defaultTemplate(){
		return ['page/leadprofile'];
	}
}
