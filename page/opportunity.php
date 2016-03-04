<?php
namespace xepan\marketing;

class page_opportunity extends \Page{

	public $title="Opportunity";

	function init(){
		parent::init();

		$opportunity=$this->add('xepan\marketing\Model_Opportunity');

		$crud=$this->add('xepan\hr\CRUD',null,null,['grid/opportunity-grid']);

		$crud->setModel($opportunity);
		
		$crud->grid->addQuickSearch(['lead']);

		$crud->add('xepan\base\Controller_Avatar',['name_field'=>'lead']);

	}

}