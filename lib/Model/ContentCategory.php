<?php

namespace xepan\marketing;  

class Model_ContentCategory extends \Model_Table{

	function init(){
		parent::init();

		$this->hasMany('xepan\marketing\Content');
		

	}
} 