<?php

namespace xepan\marketing;
	
class page_lead extends \Page{
	public $title="Lead";
	function init(){
		parent::init();

	}

	function defaultTemplate(){

		return ['page/lead'];
	}
}