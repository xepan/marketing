<?php

namespace xepan\marketing;  

class Model_ContentCategory extends \xepan\base\Model_Document{

	public $status=[

	];
	public $actions=[
		'*'=>[
			'add',
			'view'
			'edit'
			'delete'
		]
	];

	function init(){
		parent::init();

		$this->addField('name');

		$this->hasMany('xepan\marketing\Content','category_id');
		
		$this->addExpression('content_count')->set($this->refSQL('xepan\marketing\Content')->count());

	}
} 