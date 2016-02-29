<?php
namespace xepan\marketing;

class page_opportunity extends \Page{

	public $title="Opportunity";

	function init(){
		parent::init();

		$opportunity=$this->add('xepan\marketing\Model_Opportunity');

		$crud=$this->add('xepan\hr\CRUD',null,null,['grid/opportunity-grid']);

		$crud->setModel($opportunity)->debug();
		
		$crud->grid->addQuickSearch(['lead']);

	}

}