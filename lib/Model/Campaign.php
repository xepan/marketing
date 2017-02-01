<?php

namespace xepan\marketing;  

class Model_Campaign extends \xepan\hr\Model_Document{

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
		$camp_j->addField('campaign_type')->setValueList(['subscription'=>'subscription','campaign'=>'calendar']);
		
		$camp_j->hasMany('xepan\marketing\Schedule','campaign_id');
		$camp_j->hasMany('xepan\marketing\Campaign_Category_Association','campaign_id');
		$camp_j->hasMany('xepan\marketing\Campaign_SocialUser_Association','campaign_id');
		
		$this->addCondition('type','Campaign');
		$this->getElement('status')->defaultValue('Draft');

		$this->addExpression('total_visitor')->set(function($m,$q){
			return $this->add('xepan\marketing\Model_LandingResponse')
					->addCondition('campaign_id',$q->getField('id'))
					->count();
		});

		$this->addExpression('started_since')->set(function($m,$q){
			return $m->dsql()->expr("DATEDIFF('[1]',[0])",[$m->getElement('starting_date'),$this->app->today]);
		});

		$this->addExpression('remaining_duration')->set(function($m,$q){
			return $m->dsql()->expr("DATEDIFF([0],'[1]')",[$m->getElement('ending_date'),$this->app->today]);
		});

		// Todays day [depending upon oldest lead joining day]
		$this->addExpression('todays_day')->set(function($m,$q){			
			$scheduleNewsletter = $this->add('xepan\marketing\Model_Campaign_ScheduledNewsletters');
			$scheduleNewsletter->addCondition('lead_campaing_id',$m->getElement('id'));
			$scheduleNewsletter->setOrder('days_from_join','desc');
			$scheduleNewsletter->setLimit(1);
			return $scheduleNewsletter->fieldQuery('days_from_join');
		});

		$this->addExpression('calendar_schedule_left')->set(function($m,$q){
			$schedule = $this->add('xepan\marketing\Model_Schedule');
			$schedule->addCondition('campaign_id',$m->getElement('id'));
			$schedule->addCondition('date','>=',$this->app->now);
			return $schedule->count();
		});

		$this->addExpression('subscription_schedule_left')->set(function($m,$q){
			$schedule = $this->add('xepan\marketing\Model_Schedule');
			$schedule->addCondition('campaign_id',$m->getElement('id'));
			$schedule->addCondition('day','>=',$m->getElement('todays_day'));
			return $schedule->count();
		});

		$this->addExpression('schedule_left')->set(function($m,$q){
			return $q->expr("IF([campaign_type] = 'campaign',[calendar_schedule_left],[subscription_schedule_left])",
								[
									'campaign_type' => $m->getElement('campaign_type'),
									'calendar_schedule_left' => $m->getElement('calendar_schedule_left'),
									'subscription_schedule_left' => $m->getElement('subscription_schedule_left')
								]
							);
		});

		// social posts posted
		$this->addExpression('social_postings_posted_count')->set(function($m,$q){			
			return $this->add('xepan\marketing\Model_SocialPosters_Base_SocialPosting')
						->addCondition('campaign_id',$m->getElement('id'))
						->addCondition('posted_on','<>',null)
						->count();	
		});

		// newsletter posted
		$this->addExpression('newsletter_sent_count')->set(function($m,$q){
			$comm_m = $m->add('xepan\communication\Model_Communication');
			$comm_m->join('schedule','related_id')
					->addField('campaign_id');
			return $comm_m->addCondition('campaign_id',$q->getField('id'))->count();
		});

		// newsletter and social posts posted
		$this->addExpression('total_combined_postings_done')->set(function($m,$q){
			return $m->dsql()->expr("([0]+[1])",[$m->getElement('social_postings_posted_count'),$m->getElement('newsletter_sent_count')]);
		});


		$this->addExpression('newsletter_remaining')->set(function($m,$q){
				$leads = $this->add('xepan\marketing\Model_Campaign_ScheduledNewsletters',['table_alias'=>'rem_c','pass_group_by'=>true]);
				$leads->addCondition('sendable',true);
				$leads->addCondition('campaign_status','Approved');
				$leads->addCondition('lead_campaing_id',$m->getElement('id'));
				$leads->addCondition('is_already_sent',0);
				$leads->addCondition($q->expr('[0]',[$leads->getElement('lead_campaing_id')]),$q->getField('id'));
				$leads->addCondition('document_type','Newsletter');
				return $leads->count();
		});


		$this->addExpression('social_post_remaining')->set(function($m,$q){
			return  $this->add('xepan\marketing\Model_Schedule')
						->addCondition('campaign_id',$m->getElement('id'))
						->addCondition('posted_on',null)
						->addCondition('content_type','SocialPost')
						->count();
		});

		// newsletter and social post combined {remaining}
		$this->addExpression('total_remaining')->set(function($m,$q){
			return $q->expr('[0]+[1]',[$m->getElement('social_post_remaining'),$m->getElement('newsletter_remaining')]);
		});

		$this->addExpression('total')->set(function($m,$q){
			return $m->dsql()->expr("([0]+[1])",[$m->getElement('total_combined_postings_done'),$m->getElement('total_remaining')]);
		});
	
		$this->addExpression('completed_percentage')->set(function($m, $q){
			return $q->expr(
					"IFNULL(ROUND(([total_combined_postings_done]/([total_combined_postings_done]+[total_remaining]))*100,0),0)",
					[
						'campaign_type'=> $m->getElement('campaign_type'),
						'total_combined_postings_done'=> $m->getElement('total_combined_postings_done'),
						'total_remaining'=> $m->getElement('total_remaining'),
					]
					);
		});


		/*************************************************
		  EXPRESSIONS TO SHOW ERROR MESSAGE ON CAMPAIGN 
		**************************************************/
		$this->addExpression('has_schedule')->set(function($m,$q){
			return $this->add('xepan\marketing\Model_Schedule')
						->addCondition('campaign_id',$m->getElement('id'))
						->count();
		})->type('boolean');

		$this->addExpression('content_not_approved')->set(function($m,$q){
			return $this->add('xepan\marketing\Model_Schedule')
						->addCondition('campaign_id',$m->getElement('id'))
						->addCondition('document_status',['Draft','Submitted','Rejected'])
						->count();
		})->type('boolean');

		$this->addExpression('has_social_schedule')->set(function($m,$q){
			return $this->add('xepan\marketing\Model_Schedule')
						->addCondition('campaign_id',$m->getElement('id'))
						->addCondition('document_type','SocialPost')
						->count();
		})->type('boolean');

		$this->addExpression('has_newsletter_schedule')->set(function($m,$q){
			return $this->add('xepan\marketing\Model_Schedule')
						->addCondition('campaign_id',$m->getElement('id'))
						->addCondition('document_type','Newsletter')
						->count();
		})->type('boolean');

		$this->addExpression('socialuser_count')->set(function($m,$q){
			return $this->add('xepan\marketing\Model_Campaign_SocialUser_Association')
						->addCondition('campaign_id',$m->getElement('id'))
						->count();
		})->type('boolean');

		$this->addExpression('category_count')->set(function($m,$q){
			return $this->add('xepan\marketing\Model_Campaign_Category_Association')
						->addCondition('campaign_id',$m->getElement('id'))
						->count();
		})->type('boolean');

		$this->is([
				'title|to_trim|unique',
			]);


		$this->addHook('beforeSave',$this);
		$this->addHook('beforeSave',[$this,'updateSearchString']);
		$this->addHook('beforeDelete',[$this,'checkExistingCampaignCategoryAssociation']);
		$this->addHook('beforeDelete',[$this,'checkExistingCampaignSocialUserAssociation']);
		$this->addHook('beforeDelete',[$this,'checkExistingSchedule']);
		$this->addHook('beforeDelete',[$this,'checkExistingLandingResponses']);
		$this->addHook('beforeDelete',[$this,'checkExistingSocialPostings']);
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
	
	function beforeSave($m){
		if($this['starting_date'] == null)
			throw $this->exception('Starting date and ending date cannot be null','ValidityCheck')->setField('starting_date');
		
		if($this['ending_date'] == null)
			throw $this->exception('Ending date and ending date cannot be null','ValidityCheck')->setField('ending_date');
		
		if($this['ending_date'] < $this['starting_date'])
			throw $this->exception('Ending date cannot be smaller then starting date','ValidityCheck')->setField('ending_date');

		if($this['campaign_type'] == null)
			throw $this->exception('Campaign type cannot be null','ValidityCheck')->setField('campaign_type');		
	}

	function checkExistingCampaignCategoryAssociation($m){
		$m->ref('xepan\marketing\Campaign_Category_Association')->deleteAll();
	}

	function checkExistingCampaignSocialUserAssociation($m){
		$m->ref('xepan\marketing\Campaign_SocialUser_Association')->deleteAll();		
	}

	function checkExistingSchedule($m){
		$m->ref('xepan\marketing\Schedule')->deleteAll();
	}

	function checkExistingLandingResponses(){
		$model_landingresponse = $this->add('xepan\marketing\Model_LandingResponse');
		$model_landingresponse->addCondition('campaign_id',$this->id)->deleteAll();
	}

	function checkExistingSocialPostings(){
		$model_socialposting = $this->add('xepan\marketing\Model_SocialPosters_Base_SocialPosting');
		$model_socialposting->addCondition('campaign_id',$this->id)->deleteAll();
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
		     			->addCondition('created_at',$this->app->now)
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
            ->addActivity(" Campaign : '".$this['title']."' Submitted For Approval [ Based On Type : '".ucfirst($this['campaign_type'])."']", $this->id,null,null,null,"xepan_marketing_subscriberschedule&campaign_id=".$this->id."")
            ->notifyWhoCan('approve,redesign','Submitted',$this);
        $this->saveAndUnload();    
	}

	function redesign(){
		$this['status']='Redesign';
        $this->app->employee
            ->addActivity(" Campaign : '".$this['title']."' is being proceed to Redesigned [ Based On Type : '".ucfirst($this['campaign_type'])."']", $this->id,null,null,null,"xepan_marketing_subscriberschedule&campaign_id".$this->id."")
            ->notifyWhoCan('submit,schedule','Redesign',$this);
        $this->saveAndUnload();     
	}


	function onhold(){
		$this['status']='Onhold';
        $this->app->employee
            ->addActivity(" Campaign : '".$this['title']."' putting On-Hold [ Based On Type : '".ucfirst($this['campaign_type'])."']", $this->id,null,null,null,"xepan_marketing_subscriberschedule&campaign_id".$this->id."")
            ->notifyWhoCan('redesign','Onhold',$this);
		$this->saveAndUnload(); 	
		
	}

	function approve(){
		$this['status']='Approved';
        $this->app->employee
            ->addActivity( "Campaign : '".$this['title']."' Approved [ Based On Type : '".ucfirst($this['campaign_type'])."']", $this->id,null,null,null,"xepan_marketing_subscriberschedule&campaign_id".$this->id."")
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