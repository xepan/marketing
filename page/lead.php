<?php

namespace xepan\marketing;
	
class page_lead extends \Page{
	public $title = "Lead";
	function init(){
		parent::init();

		$lead = $this->add('xepan\marketing\Model_Lead');
		$crud = $this->add('xepan\hr\CRUD',
						[
							'action_page'=>'xepan_marketing_leaddetails',
							'grid_options'=>[
											'defaultTemplate'=>['grid/lead-grid']
											]
						]);
		$crud->setModel($lead);
		$crud->grid->addQuickSearch(['name']);

	}
}