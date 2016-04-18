<?php



namespace xepan\marketing;

class page_tests_init extends \AbstractController{
	function init(){
		parent::init();

		$this->app->xepan_app_initiators['xepan\marketing']->resetDB();
		

	}
}