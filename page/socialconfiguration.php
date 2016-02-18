<?php
namespace xepan\marketing;
class page_socialconfiguration extends \Page{
	public $title="Social";
	function init(){
		parent::init();
	}

	function defaultTemplate(){

		return['page/socialconfiguration'];
	}
}