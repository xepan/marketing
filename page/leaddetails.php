<?php

namespace xepan\marketing;

class page_leaddetails extends \Page {
	public $title ='Lead Details';

	function init(){
		parent::init();

		$lead= $this->add('xepan\marketing\Model_Lead')->tryLoadBy('id',$this->api->stickyGET('contact_id'));
		
		$lead_view = $this->add('xepan\base\View_Contact',null,'contact_view');
		$lead_view->setModel($lead);

		$detail = $this->add('xepan\hr\View_Document',['action'=> $action],'details',['view/details']);
		$detail->setModel($lead,['source','marketing_category','communication','opportunities'],['source','marketing_category_id','communication','opportunities']);

		if($lead->loaded()){
			$opp = $lead->ref('xepan\marketing\Opportunity');
			$crud = $this->add('xepan\hr\CRUD',null,'opportunity',['grid/addopportunity-grid']);
			$crud->setModel($opp);
			$crud->grid->addQuickSearch(['name']);
			
		}
		$activity_view = $this->add('xepan\marketing\View_activity',null,'activity');
	}

	function defaultTemplate(){
		return ['page/leadprofile'];
	}
}
