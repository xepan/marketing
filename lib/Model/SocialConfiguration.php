<?php

namespace xepan\marketing;  

class Model_SocialConfiguration extends \Model_Document{

	public $status=[

	];
	public $actions=[
		'*'=>[
			'add',
			'view',
			'edit',
			'delete'
		]
	];

	function init(){
		parent::init();

		$this->addField('name');
		$this->addField('configuration');		

	}
} 