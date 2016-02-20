<?php

namespace xepan\marketing;
	
class page_lead extends \Page{
	public $title="Lead";
	function init(){
		parent::init();

		$lead=$this->add('xepan\marketing\Model_Lead');
		$crud=$this->add('xepan\base\CRUD',array('grid_class'=>'xepan\base\Grid','grid_options'=>array('defaultTemplate'=>['grid/lead-grid'])));
		$crud->setModel($lead);
		$crud->grid->addQuickSearch(['name']);

	}

	// function defaultTemplate(){

	// 	return ['page/lead'];
	// }
}