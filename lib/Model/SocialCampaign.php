<?php

namespace xepan\marketing;

class Model_SocialCampaign extends \xepan\base\Model_Document{

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
		
		$cat_j = $this->join('social_campaign.document_id');
		$cat_j->addField('name');
		
		$cat_j->hasMany('xepan\marketing\Lead','lead_id');

		$this->addCondition('type','SocialCampaign');

	}
}
