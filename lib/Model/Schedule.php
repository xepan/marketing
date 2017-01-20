<?php

namespace xepan\marketing;

class Model_Schedule extends \xepan\base\Model_Table{
	public $table = "schedule";
	function init(){
		parent::init();

		$this->hasOne('xepan/marketing/Campaign','campaign_id');
		$this->hasOne('xepan/marketing/Content','document_id');
		$this->addField('date')->type('datetime');
		$this->addField('client_event_id');
		$this->addField('day')->type('Number');
		$this->addField('posted_on')->type('datetime');
		
		$this->addExpression('content_type')->set($this->refSQL('document_id')->fieldQuery('type'));
	
		$this->addExpression('sent')->set(function($m,$q){
			$comm_m = $this->add('xepan\communication\Model_Communication');
			$comm_m->addCondition('related_id',$m->getElement('id'));
			return $comm_m->count();		
		})->type('boolean');
	}

	function campaign(){
		if(!$this->loaded())
			throw new \Exception("schedule must loaded");
			
		return $this->add('xepan/marketing/Model_Campaign')->load($this['campaign_id']);
	}

	function campaignSocialUser(){
		$campaign = $this->campaign();
		$association = $this->add('xepan/marketing/Model_Campaign_SocialUser_Association')
				->addCondition('campaign_id',$campaign->id);

		$association->addExpression('configuration')->set(function($m,$q){
			return $m->refSQL('socialuser_id')->fieldQuery('configuration');
		});

		return $association;
	}

}