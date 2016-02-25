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
		'Draft'=>['view','submit','edit','delete'],
		'Submitted'=>['view','reject','approve','edit','delete'],
		'Approved'=>['view','reject','email','edit','delete'],
		'Reject'=>['view','submit','edit','delete']
	];

	function init(){
		parent::init();

		$cont_j=$this->join('content.document_id');
		$cont_j->hasone('xepan\marketing\ContentCategory','category_document_id');
		$cont_j->addField('short_content');
		$cont_j->addField('long_content')->type('text');

	}
} 