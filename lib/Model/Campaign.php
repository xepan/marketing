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
		$camp_j->addField('title');
		$camp_j->addField('schedule')->defaultValue('[]');
		$camp_j->addField('starting_date')->type('datetime');
		$camp_j->addField('ending_date')->type('datetime');
		$camp_j->addField('campaign_type')->enum(['subscription','campaign']);
		
		$camp_j->hasMany('xepan\marketing\Schedule','campaign_id');
		$camp_j->hasMany('xepan\marketing\Campaign_Category_Association','campaign_id');
		$camp_j->hasMany('xepan\marketing\Campaign_SocialUser_Association','campaign_id');
		
		$this->addCondition('type','Campaign');
		$this->getElement('status')->defaultValue('Draft');
		
		$this->addHook('beforeSave',$this);
		$this->addHook('beforeDelete',[$this,'checkExistingCampaignCategoryAssociation']);
		$this->addHook('beforeDelete',[$this,'checkExistingCampaignSocialUserAssociation']);
		$this->addHook('beforeDelete',[$this,'checkExistingSchedule']);
	}


	function schedule(){
		$this->load($this->id);
	
		if($this['campaign_type']=='campaign'){
			$this->app->redirect($this->api->url('xepan/marketing/schedule',['campaign_id'=>$this->id]));
		}else{
			$this->app->redirect($this->api->url('xepan/marketing/subscriberschedule',['campaign_id'=>$this->id]));
		}
		
	}
	
	function beforeSave($m){}

	function checkExistingCampaignCategoryAssociation($m){
		$campaign_catasso_count = $m->ref('xepan\marketing\Campaign_Category_Association')->count()->getOne();
		
		if($campaign_catasso_count)
			throw $this->exception('Cannot Delete,first delete Campaign`s  Category Assciation`s ');
	}

	function checkExistingCampaignSocialUserAssociation($m){
		$cam_user_count = $m->ref('xepan\marketing\Campaign_SocialUser_Association')->count()->getOne();
		
		if($cam_user_count)
			throw $this->exception('Cannot Delete,first delete Campaign`s  Social User`s ');	
	}

	function checkExistingSchedule($m){
		$schedule_count = $m->ref('xepan\marketing\Schedule')->count()->getOne();

		if($schedule_count)
			throw $this->exception('Cannot Delete,first delete  Schedule`s ');

	}

	function getAssociatedCategories(){

		$associated_categories = $this->ref('xepan\marketing\Campaign_Category_Association')
								->_dsql()->del('fields')->field('marketing_category_id')->getAll();
		return iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($associated_categories)),false);
	}

	function getAssociatedUsers(){

		$associated_users = $this->ref('xepan\marketing\Campaign_SocialUser_Association')
								->_dsql()->del('fields')->field('socialuser_id')->getAll();
		return iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($associated_users)),false);
	}

	function removeAssociateCategory(){
		$this->ref('xepan\marketing\Campaign_Category_Association')->deleteAll();
	}

	function removeAssociateUser(){
		$this->ref('xepan\marketing\Campaign_SocialUser_Association')->deleteAll();
	}

	function submit(){
		$this['status']='Submitted';
        $this->app->employee
            ->addActivity("Submitted Campaign", $this->id)
            ->notifyWhoCan('approve,redesign,onhold','Submitted');
        $this->saveAndUnload();    
	}

	function redesign(){
		$this['status']='Redesign';
        $this->app->employee
            ->addActivity("Rejected Campaign", $this->id)
            ->notifyWhoCan('submit,','Redesign');
        $this->saveAndUnload();     
	}


	function onhold(){
		$this['status']='Onhold';
        $this->app->employee
            ->addActivity("Put Campaign onhold", $this->id)
            ->notifyWhoCan('redesign','Onhold');
		$this->saveAndUnload(); 	
		
	}

	function approve(){
		$this['status']='Approved';
        $this->app->employee
            ->addActivity("Approved Campaign", $this->id)
            ->notifyWhoCan('?????','Approved');
		$this->saveAndUnload(); 
	}

	function getSchedule(){
		if(!$this->loaded())
			throw new \Exception("Campaign model must loaded");


		$schedule = $this->ref('xepan\marketing\Schedule')
								->_dsql()->del('fields')->field('id')->getAll();
		return iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($schedule)),false);
			
	}

} 