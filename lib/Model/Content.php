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
		'Submitted'=>['view','reject','approve','edit','delete'],
		'Approved'=>['view','reject','email','edit','delete'],
		'Reject'=>['view','edit','delete','submit']
	];

	function init(){
		parent::init();

		$cont_j=$this->join('content.document_id');
		$cont_j->hasone('xepan\marketing\MarketingCategory','marketing_category_id');
		$cont_j->addField('short_content');
		$cont_j->addField('long_content')->type('text');
		$cont_j->addField('title');
		$cont_j->addField('is_template')->type('boolean')->defaultValue(false);

		$this->getElement('status')->defaultValue('Draft');
		//$this->addCondition('type','Content');

	}
} 