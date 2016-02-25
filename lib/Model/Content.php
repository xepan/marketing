<?php

namespace xepan\marketing;  

class Model_Content extends \xepan\base\Model_Document{

	public $status=[
		'Draft',
		'Submitted',
		'Approved',
		'Reject'
	];
	public $actions=[
		'Draft'=>['view','edit','delete','submit'],
		'Submitted'=>['view','edit','delete','reject','approve'],
		'Approved'=>['view','edit','delete','reject','email'],
		'Reject'=>['view','edit','delete','submit']
	];

	function init(){
		parent::init();

		$cont_j=$this->join('content.document_id');
		$cont_j->hasone('xepan\marketing\ContentCategory','category_id');
		$cont_j->addField('short_content');
		$cont_j->addField('long_content')->type('text');

	}
} 