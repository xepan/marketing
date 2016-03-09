<?php

namespace xepan\marketing;

class Model_CampaignCategory extends \xepan\base\Model_Document{

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
		
		$cat_j->hasMany('xepan\marketing\MarketingCategory','campaign_category_id');
		$cat_j->hasMany('xepan\marketing\Campaign','campaign_category_id');
		//$this->addCondition('type','CampaignCategory');

	}
}
