<?php

/**
* description: ATK Page
* 
* @author : Gowrav Vishwakarma
* @email : gowravvishwakarma@gmail.com, info@xavoc.com
* @website : http://xepan.org
* 
*/

namespace xepan\marketing;

class page_test extends \Page {
	public $title='TITLE';

	function init(){
		parent::init();
		$this->add('View')->set('Ooops');
	}
}
