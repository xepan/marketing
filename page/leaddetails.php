<?php

namespace xepan\marketing;

class page_leaddetails extends \Page {
	public $title ='Lead Details';

	function init(){
		parent::init();

		$lead= $this->add('xepan\marketing\Model_Lead')->tryLoadBy('id',$this->api->stickyGET('contact_id'));
		
		$lead_view = $this->add('xepan\base\View_Contact',null,'contact_view');
		$lead_view->setModel($lead);

		$doc = $this->add('xepan\base\View_Document',
				[
					'action'=>$this->api->stickyGET('action')?:'view', // add/edit
					'id_fields_in_view'=>[],
					'allow_many_on_add' => false, // Only visible if editinng,
					'view_template' => ['view/details']
				],'details'
			);

		$doc->setModel($lead,['source','category'],['source','category_id']);

		$activity_view = $this->add('xepan\marketing\View_activity',null,'activity');
		$opportunity_view = $this->add('xepan\marketing\View_opportunity',null,'opportunity');
	}

	function defaultTemplate(){
		return ['page/leadprofile'];
	}
}
