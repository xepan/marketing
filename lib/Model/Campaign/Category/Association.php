<?php

namespace xepan\marketing;

class Model_Campaign_Category_Association extends \xepan\base\Model_Document{

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
		
		$cat_j = $this->join('campaigncategory.document_id');
		$cat_j->addField('name');
		
		$cat_j->hasOne('xepan\marketing\MarketingCategory','marketing_category_id');
		$cat_j->hasOne('xepan\marketing\Campaign','Camapign_id');
		//$this->addCondition('type','CampaignCategory');

		$this->addHook('beforeSave',$this);
		$this->addHook('beforeDelete',$this);
	}

	function beforeSave($m){}

	function beforeDelete($m){
		$campaign_count = $m->ref('xepan\marketing\Campaign')->count()->getOne();
		$marketing_count = $m->ref('xepan\marketing\MarketingCategory')->count()->getOne();
		
		if($campaign_count)
			throw $this->exception('Cannot Delete,first delete Campaign`s ');	
	}
}
