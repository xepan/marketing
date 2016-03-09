<?php

namespace xepan\marketing;

class Model_CampaignCategory extends \xepan\base\Model_Table{
	public $table = "camapigncategory";

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
	public $acl=false;

	function init(){
		parent::init();
		
		$this->hasOne('xepan\marketing\MarketingCategory','marketingcategory_id');
		$this->hasOne('xepan\marketing\Campaign','campaign_id');
		$this = $this->join('campaigncategory.document_id');
		$this->addField('name');

	}
}
