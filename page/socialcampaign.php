<?php
namespace xepan\marketing;

class page_socialcampaign extends \Page{

	public $title = "Social Management";
	function init(){
		parent::init();

		$m=$this->add('xepan\marketing\Model_SocialPost');

		// $crud=$this->add('xepan\hr\CRUD',array('grid_class'=>'xepan\base\Grid','grid_options'=>array('defaultTemplate'=>['grid/lead-grid'])));

		// $crud->setModel($mgmt);
		// $crud->grid->addQuickSearch(['name']);
	}
}