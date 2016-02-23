<?php

namespace xepan\marketing;

class page_leaddetails extends \Page {
	public $title ='Lead Details';

	function init(){
		parent::init();

		$lead= $this->add('xepan\marketing\Model_Lead')->tryLoadBy('id',$this->api->stickyGET('contact_id'));
		
		$lead_view = $this->add('xepan\base\View_Contact',null,'contact_view');
		$lead_view->setModel($lead);
		$this->add('xepan\marketing\View_activity',null,'activity');
		$this->add('xepan\marketing\View_opportunity',null,'opportunity');
	}

	function defaultTemplate(){
		return ['page/leadprofile'];
	}
}
