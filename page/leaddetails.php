<?php

namespace xepan\marketing;

class page_leaddetails extends \Page {
	public $title ='Lead Details';

	function init(){
		parent::init();

		$action = $this->api->stickyGET('action')?:'view';
		$lead= $this->add('xepan\marketing\Model_Lead')->tryLoadBy('id',$this->api->stickyGET('contact_id'));
		$lead->tryLoadAny();
		$lead_view = $this->add('xepan\base\View_Contact',null,'contact_view');
		$lead_view->setModel($lead);

		$detail = $this->add('xepan\hr\View_Document',['action'=> $action],'details',['view/details']);
		$detail->setModel($lead,['source','marketing_category','communication','opportunities'],['source','marketing_category_id','communication','opportunities']);

		$opportunities_tab = $this->add('xepan\hr\View_Document',['action'=> $action],'opportunity',['view/opp']);
		$opportunities_tab->setModel($lead,['no_fields_in_view'],['no_field_in_form']);

		if($lead->loaded()){
			$o = $opportunities_tab->addMany('opportunity',null,'opportunity',['grid/addopportunity-grid']);
			$o->setModel('xepan/marketing/Opportunity');
		}


		$activity_view = $this->add('xepan\marketing\View_activity',null,'activity');
	}

	function defaultTemplate(){
		return ['page/leadprofile'];
	}
}
