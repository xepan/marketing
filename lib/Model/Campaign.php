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
		'Draft'=>['view','edit','delete','submit','schedule'],
		'Submitted'=>['view','edit','delete','approve','redesign'],
		'Redesign'=>['view','edit','delete','submit','schedule'],
		'Approved'=>['view','edit','delete','redesign','onhold'],
		'Onhold'=>['view','edit','delete','redesign']
	];

	function init(){
		parent::init();

		$camp_j=$this->join('campaign.document_id');
		$camp_j->hasone('xepan\marketing\Schedule','schedule_id');
		$camp_j->addField('title');
		$camp_j->addField('starting_date')->type('datetime');
		$camp_j->addField('ending_date')->type('datetime');
		$camp_j->addField('campaign_type')->hint('Based on lead creation date or as campaign date');
		
		$camp_j->hasMany('xepan\marketing\Campaign_Category_Association','campaign_id');
		$camp_j->hasMany('xepan\marketing\CampaignSocialUser','camapign_id');
		
		$this->addCondition('type','Campaign');
		$this->getElement('status')->defaultValue('Draft');
		
		$this->addHook('beforeSave',$this);
		$this->addHook('beforeDelete',$this);
	}


	function schedule(){

		$this->app->redirect($this->api->url('xepan/marketing/schedule',['campaign_id'=>$this->id]));
		
	}
	
	function beforeSave($m){}

	function beforeDelete($m){
		$campaign_catasso_count = $m->ref('xepan\marketing\Campaign_Category_Association')->count()->getOne();
		$cam_user_count = $m->ref('xepan\marketing\CampaignSocialUser')->count()->getOne();
		
		if($campaign_catasso_count or $cam_user_count)
			throw $this->exception('Cannot Delete,first delete Campaign`s Category Association And Campaign Social User ');	
	}

	function getAssociatedCategories(){

		$associated_categories = $this->ref('xepan\marketing\Campaign_Category_Association')
								->_dsql()->del('fields')->field('marketingcategory_id')->getAll();
		return iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($associated_categories)),false);
	}
} 