<?php
namespace xepan\marketing;
class page_newslettercategory extends \Page{
	public $title="Category";
	function init(){
		parent::init();
	}

	function defaultTemplate(){

		return['page/newslettercategory'];
	}
}