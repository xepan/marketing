<?php
namespace xepan\marketing;
class page_opportunity extends \Page{
	public $title="Opportunity";
	function init(){
		parent::init();

		$opportunity=$this->add('xepan\marketing\Model_Opportunity');

		$crud=$this->add('xepan\base\CRUD',array('grid_class'=>'xepan\base\Grid','grid_options'=>array('defaultTemplate'=>['grid/opportunity-grid'])));

		// $crud=$this->add('xepan\base\CRUD',
		// 		[
		// 			'action_page'=>'xepan_hr_employeedetail',
		// 			'grid_options'=>[
		// 							'defaultTemplate'=>['grid/opportunity-grid']
		// 							]
		// 		]);

		$crud->setModel($opportunity);
		$crud->grid->addQuickSearch(['name']);
	}

	// function defaultTemplate(){

	// 	return['page/opportunity'];
	// }
}