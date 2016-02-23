<?php

namespace xepan\marketing;  

class Model_ContentCategory extends \xepan\base\Model_Document{

	function init(){
		parent::init();

		$this->addField('name');

		$this->hasMany('xepan\marketing\Content','category_id');
		
		$this->addExpression('content_count')->set($this->refSQL('xepan\marketing\Content')->count());

	}
} 