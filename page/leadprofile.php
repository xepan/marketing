<?php
namespace xepan\marketing;
class page_leadprofile extends \Page{
		public $title="Lead Profile";
	function init(){
		parent::init();

		$this->add('View_Marketing_activity',null,'activity');
		$this->add('View_Marketing_opportunity',null,'opportunity');

	}

	function defaultTemplate(){

		return ['page/leadprofile'];
	}
}