<?php
namespace xepan\marketing;
class page_leadcategorymgmt extends \Page{
	public $title="Management";
	function init(){
		parent::init();

		$mgmt=$this->add('xepan\marketing\Model_LeadCategory');

		$crud=$this->add('xepan\base\CRUD',array('grid_class'=>'xepan\base\Grid','grid_options'=>array('defaultTemplate'=>['grid/lead-grid'])));

		$crud->setModel($mgmt);
		$crud->grid->addQuickSearch(['name']);
	}

	// function defaultTemplate(){

	// 	return['page/opportunity'];
	// }
}