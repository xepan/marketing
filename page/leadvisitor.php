<?php

namespace xepan\marketing;
	
class page_leadvisitor extends \xepan\base\Page{
	public $title = "Total Visits";
	function init(){
		parent::init();

		$lead_id=$this->app->stickyGET('contact_id');
		
		$visitors=$this->add('xepan\marketing\Model_LandingResponse');
		$visitors->addCondition('contact_id',$lead_id);
		$visitors->tryLoadAny();
		$crud = $this->add('xepan\hr\CRUD');
		$crud->setModel($visitors);
		$crud->grid->addPaginator(50);
	}
}