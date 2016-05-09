<?php

namespace xepan\marketing;

class page_dashboard extends \xepan\base\Page{
	public $title = "Dashboard";
	function init(){
		parent::init();
	}

	function defaultTemplate(){
		return['page\dashboard'];
	}

}