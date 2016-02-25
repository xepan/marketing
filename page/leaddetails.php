<?php

namespace xepan\marketing;

class page_leaddetails extends \Page {
	public $title ='Lead Details';

	function init(){
		parent::init();

		$lead= $this->add('xepan\marketing\Model_Lead')->tryLoadBy('id',$this->api->stickyGET('contact_id'));
		
		$lead_view = $this->add('xepan\base\View_Contact',null,'contact_view');
		$lead_view->setModel($lead);

		$detail = $this->add('xepan\base\View_Document',
				[
					'action'=>$this->api->stickyGET('action')?:'view', // add/edit
					'id_fields_in_view'=>[],
					'allow_many_on_add' => false, // Only visible if editinng,
					'view_template' => ['view/details']
				],'details'
			);

		$detail->setModel($lead,['source','category'],['source','category_id']);

		if($lead->loaded()){
			$opp = $lead->ref('xepan\marketing\Opportunity');
			$crud = $this->add('xepan\base\CRUD',
							[
								//'action_page'=>'xepan_marketing_leaddetails',
								'grid_options'=>[
												'defaultTemplate'=>['grid/addopportunity-grid']
												],
							],'opportunity');
			$crud->setModel($opp);
			$crud->grid->addQuickSearch(['name']);
			
		}
		$activity_view = $this->add('xepan\marketing\View_activity',null,'activity');
	}

	function defaultTemplate(){
		return ['page/leadprofile'];
	}
}
