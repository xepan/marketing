<?php
namespace xepan\marketing;
class page_lead extends \Page{
	public $title="Social Post";
	function init(){
		parent::init();


	}

	function defaultTemplate(){
		return ['page/addsocialpost'];
	}
}