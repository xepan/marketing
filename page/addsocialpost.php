<?php
namespace xepan\marketing;
class page_lead extends \Page{

	function init(){
		parent::init();


	}

	function defaultTemplate(){
		return ['page/marketing/addsocialpost'];
	}
}