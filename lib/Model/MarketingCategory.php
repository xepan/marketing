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
		
		$cat_j->hasMany('xepan\marketing\Lead','marketing_category_id');
		$cat_j->hasMany('xepan\marketing\Content','marketing_category_id');
		$this->addExpression('leads_count')->set($this->refSql('xepan\marketing\Lead')->count());
		$this->addCondition('type','MarketingCategory');

		$this->addHook('beforeSave',$this);
		$this->addHook('beforeDelete',$this);

	}

	function beforeSave($m){}

	function beforeDelete($m){
		$lead_count = $m->ref('xepan\marketing\Lead')->count()->getOne();
		$content_count = $m->ref('xepan\marketing\Content')->count()->getOne();
		
		if($lead_count or $content_count)
			throw $this->exception('Cannot Delete,first delete Lead`s And Contents ');	
	}
}
