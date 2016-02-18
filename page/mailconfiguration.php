<?php
namespace xepan\marketing;
class page_mailconfiguration extends \Page{

	function init(){
		parent::init();

		$this->add('View_Info')->set('hello');
	}

	function defaultTemplate(){

		return ['page/mailconfiguration'];
	}
}