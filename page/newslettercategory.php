<?php
namespace xepan\marketing;
class page_newslettercategory extends \xepan\base\Page{
	public $title="Category";
	function init(){
		parent::init();
	}

	function defaultTemplate(){

		return['page/newslettercategory'];
	}
}