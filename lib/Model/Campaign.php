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

	public $title_field='title';

	function init(){
		parent::init();

		$camp_j=$this->join('campaign.document_id');
		$camp_j->addField('title');
		$camp_j->addField('schedule')->defaultValue('[]');
		$camp_j->addField('starting_date')->type('date');
		$camp_j->addField('ending_date')->type('date');
		$camp_j->addField('campaign_type')->enum(['subscription','campaign']);
		
		$camp_j->hasMany('xepan\marketing\Schedule','campaign_id');
		$camp_j->hasMany('xepan\marketing\Campaign_Category_Association','campaign_id');
		$camp_j->hasMany('xepan\marketing\Campaign_SocialUser_Association','campaign_id');
		
		$this->addCondition('type','Campaign');
		$this->getElement('status')->defaultValue('Draft');
		
		$this->addExpression('active_duration')->set(function($m,$q){
			return $m->dsql()->expr("DATEDIFF([1],[0])",[$m->getElement('starting_date'),$this->app->today]);
		});

		$this->addExpression('remaining_duration')->set(function($m,$q){
			return $m->dsql()->expr("DATEDIFF([0],[1])",[$m->getElement('ending_date'),$this->app->today]);
		});

		$this->addExpression('posted')->set(function($m,$q){
			return "'todo'";
		});

		$this->addExpression('remaining')->set(function($m,$q){
			return "'todo'";
		});

		$this->addExpression('success_percent')->set(function($m,$q){
			return "'todo'";
		});

		$this->addExpression('total_visitor')->set(function($m,$q){
			return $this->add('xepan\marketing\Model_LandingResponse')
					->addCondition('campaign_id',$q->getField('id'))
					->count();
		});


		$this->addHook('beforeSave',$this);
		$this->addHook('beforeSave',[$this,'updateSearchString']);
		$this->addHook('beforeDelete',[$this,'checkExistingCampaignCategoryAssociation']);
		$this->addHook('beforeDelete',[$this,'checkExistingCampaignSocialUserAssociation']);
		$this->addHook('beforeDelete',[$this,'checkExistingSchedule']);
	}

	function updateSearchString($m){

		$search_string = ' ';
		$search_string .=" ". $this['title'];
		$search_string .=" ". $this['schedule'];
		$search_string .=" ". $this['starting_date'];
		$search_string .=" ". $this['ending_date'];
		$search_string .=" ". $this['campaign_type'];

		$this['search_string'] = $search_string;
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

	function getAssociatedUsers($required_avtive_only=false){
		if(!$this->loaded())
			throw new \Exception("campaign model must loaded");
			
		$associated_users = $this->ref('xepan\marketing\Campaign_SocialUser_Association');
		if($required_avtive_only)
			$associated_users->addCondition('is_active',true);

		$social_user_ids = $associated_users->_dsql()->del('fields')->field('socialuser_id')->getAll();
		return iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($social_user_ids)),false);
	}

	function associateCategory($category){
		return $this->add('xepan\marketing\Model_Campaign_Category_Association')
						->addCondition('campaign_id',$this->id)
		     			->addCondition('marketing_category_id',$category)
			 			->tryLoadAny()	
			 			->save();
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
            ->addActivity("Submitted Campaign", $this->id,null)
            ->notifyWhoCan('approve,redesign','Submitted',$this);
        $this->saveAndUnload();    
	}

	function redesign(){
		$this['status']='Redesign';
        $this->app->employee
            ->addActivity("Rejected Campaign", $this->id,null)
            ->notifyWhoCan('submit,schedule','Redesign',$this);
        $this->saveAndUnload();     
	}


	function onhold(){
		$this['status']='Onhold';
        $this->app->employee
            ->addActivity("Put Campaign onhold", $this->id,null)
            ->notifyWhoCan('redesign','Onhold',$this);
		$this->saveAndUnload(); 	
		
	}

	function approve(){
		$this['status']='Approved';
        $this->app->employee
            ->addActivity("Approved Campaign", $this->id,null)
            ->notifyWhoCan('redesign,onhold','Approved',$this);
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