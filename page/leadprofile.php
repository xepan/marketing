<?php

namespace xepan\marketing;

class page_leadprofile extends \Page{

	public $title="Lead Profile";

	function init(){
		parent::init();

		$this->add('xepan\marketing\View_activity',null,'activity');
		$this->add('xepan\marketing\View_opportunity',null,'opportunity');

	}

	function defaultTemplate(){
		return ['page/leadprofile'];
	}
}