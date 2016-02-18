<?php
namespace xepan\marketing;
class page_leadprofile extends \Page{

	function init(){
		parent::init();

		$this->add('View_Marketing_activity',null,'activity');
		$this->add('View_Marketing_opportunity',null,'opportunity');

	}

	function defaultTemplate(){

		return ['page/marketing/leadprofile'];
	}
}