<?php
namespace xepan\marketing;
class page_facebookconfig extends \Page{
	public $title="Social Post";
	function init(){
		parent::init();

	}

	function defaultTemplate(){
		return ['page/facebookconfig'];
	}
}