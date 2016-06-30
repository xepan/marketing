<?php

namespace xepan\marketing;

class page_test extends \xepan\base\Page{
	function init(){
		parent::init();

		$this->add('xepan\marketing\Tool_Subscription');
	}
}