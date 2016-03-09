<?php

namespace xepan\marketing;  

class Model_Campaign extends \xepan\base\Model_Document{

	public $status=['Draft',
					'Submitted',
				    'Redesign',
	                'Approved',
				    'Onhold'
	];
	public $actions=[
		'Draft'=>['view','edit','delete','submit'],
		'Submitted'=>['view','edit','delete','approve','redesign'],
		'Redesign'=>['view','edit','delete','submit'],
		'Approved'=>['view','edit','delete','redesign','onhold'],
		'Onhold'=>['view','edit','delete','redesign']
	];

	function init(){
		parent::init();

		$camp_j=$this->join('campaign.document_id');
		$camp_j->hasone('xepan\marketing\CampaignCategory','campaign_category_id');
		$camp_j->hasone('xepan\marketing\Schedule','schedule_id');
		$camp_j->addField('title');
		$camp_j->addField('starting_date')->type('datetime');
		$camp_j->addField('ending_date')->type('datetime');
		$camp_j->addField('campaign_type')->hint('Based on lead creation date or as campaign date');
		
		$camp_j->hasMany('xepan\marketing\CampaignSocialUser','Camapign_id');
		
		$this->addCondition('type','Campaign');
		$this->getElement('status')->defaultValue('Draft');
	}
} 