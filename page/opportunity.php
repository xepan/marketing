<?php
namespace xepan\marketing;
class page_opportunity extends \Page{
	public $title="Opportunity";
	function init(){
		parent::init();
	}

	function defaultTemplate(){

		return['page/opportunity'];
	}
}