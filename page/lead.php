<?php

namespace xepan\marketing;
	
class page_lead extends \Page{
	public $title="Lead";
	function init(){
		parent::init();

		$this->add('View_Info')->set('hello');
	}

	function defaultTemplate(){

		return ['page/lead'];
	}
}