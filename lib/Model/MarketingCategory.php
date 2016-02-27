<?php

namespace xepan\marketing;

class Model_MarketingCategory extends \xepan\base\Model_Document{

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
		
		$cat_j = $this->join('marketingcategory.document_id');
		$cat_j->addField('name');
		
		$cat_j->hasMany('xepan\marketing\Lead','lead_id');

		$this->addCondition('type','MarketingCategory');

	}
}
