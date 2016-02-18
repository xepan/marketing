<?php
namespace xepan\marketing;
class page_opportunity extends \Page{

	function init(){
		parent::init();
	}

	function defaultTemplate(){

		return['page/marketing/opportunity'];
	}
}