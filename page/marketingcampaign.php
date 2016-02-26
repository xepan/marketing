<?php
namespace xepan\marketing;

class page_marketingcampaign extends \Page{

	public $title = "Marketing Campaign";
	function init(){
		parent::init();

		$m = $this->add('xepan\marketing\Model_MarketingCampaign');

		// $crud=$this->add('xepan\hr\CRUD',array('grid_class'=>'xepan\base\Grid','grid_options'=>array('defaultTemplate'=>['grid/lead-grid'])));

		// $crud->setModel($mgmt);
		// $crud->grid->addQuickSearch(['name']);
	}

	function defaultTemplate(){

		return['page/marketingcampaign'];
	}

}