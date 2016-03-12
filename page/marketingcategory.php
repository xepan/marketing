<?php
namespace xepan\marketing;

class page_marketingcategory extends \Page{

	public $title = "Marketing Category";
	function init(){
		parent::init();

		$m = $this->add('xepan\marketing\Model_MarketingCategory');

		$crud = $this->add('xepan\hr\CRUD',null,null,['grid/category-grid']);

		$crud->setModel($m);
	    $crud->grid->addQuickSearch(['name']);
	}

	function defaultTemplate(){

		return['page/marketingcampaign'];
	}

}