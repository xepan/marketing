<?php

namespace xepan\marketing;

class Initiator extends \Controller_Addon {
	public $addon_name = 'xepan_marketing';

	function init(){
		parent::init();

		if($_GET['xepan_landing_contact_id'] || $_GET['xepan_landing_campaign_id'] || $_GET['xepan_landing_content_id'] || $_GET['xepan_landing_emailsetting_id'])
			$this->landingResponse();	
	}

	function landingResponse(){		
		$model_landingresponse = $this->add('xepan\marketing\Model_LandingResponse');
		$model_landingresponse['contact_id'] = $_GET['xepan_landing_contact_id'];
		$model_landingresponse['campaign_id'] = $_GET['xepan_landing_campaign_id'];
		$model_landingresponse['content_id'] = $_GET['xepan_landing_content_id'];
		$model_landingresponse['emailsetting_id'] = $_GET['xepan_landing_emailsetting_id'];
		$model_landingresponse['date'] = $this->app->now;
		$model_landingresponse['type'] = $_GET['source']?:"Unknown";
		$model_landingresponse['referrersite'] = $_GET['xepan_landing_referersite']?:$_SERVER['HTTP_REFERER'];
		$model_landingresponse['ip'] = $_SERVER['REMOTE_ADDR'];
		$model_landingresponse->save();
		$this->app->hook('pointable_event',['landing_response',['lead'=>$model_landingresponse->ref('contact_id'),'response'=>$model_landingresponse]]);
		
	}

	function setup_admin(){

		$this->routePages('xepan_marketing');
		$this->addLocation(array('template'=>'templates','js'=>'templates/js','css'=>'templates/css'))
		->setBaseURL('../vendor/xepan/marketing/');

		$m = $this->app->top_menu->addMenu('Marketing');
		$m->addItem(['Dashboard','icon'=>'fa fa-dashboard'],'xepan_marketing_dashboard');
		$m->addItem(['Strategy Planning','icon'=>'fa fa-gavel'],'xepan_marketing_strategyplanning');
		$m->addItem(['Category Management','icon'=>'fa fa-sitemap'],'xepan_marketing_marketingcategory');
		$m->addItem(['Lead','icon'=>'fa fa-users'],$this->app->url('xepan_marketing_lead',['status'=>'Active']));
		$m->addItem(['Opportunity','icon'=>'fa fa-user'],$this->api->url('xepan_marketing_opportunity',['watchable'=>true]));
		$m->addItem(['Newsletter','icon'=>'fa fa-envelope-o'],$this->app->url('xepan_marketing_newsletter',['status'=>'Draft,Submitted,Approved']));
		$m->addItem(['Social Content','icon'=>'fa fa-globe'],$this->app->url('xepan_marketing_socialcontent',['status'=>'Draft,Submitted,Approved']));
		$m->addItem(['Tele Marketing','icon'=>'fa fa-phone'],'xepan_marketing_telemarketing');
		$m->addItem(['SMS','icon'=>'fa fa-envelope-square'],$this->app->url('xepan_marketing_sms',['status'=>'Draft,Submitted,Approved']));
		$m->addItem(['Campaign','icon'=>'fa fa-bullhorn'],$this->app->url('xepan_marketing_campaign',['status'=>'Draft,Submitted,Redesign,Approved,Onhold']));
		$m->addItem(['Day by Day Analytics','icon'=>'fa fa-graph'],$this->app->url('xepan_marketing_daybydayanalytics'));
		// $m->addItem(['Reports','icon'=>'fa fa-cog'],'xepan_marketing_report');
		$m->addItem(['Configuration','icon'=>'fa fa-cog'],'xepan_marketing_socialconfiguration');
		
        $this->app->status_icon["xepan\marketing\Model_Lead"] = ['All'=>'fa fa-globe','Active'=>"fa fa-circle text-success",'InActive'=>'fa fa-circle text-danger'];
        $this->app->status_icon["xepan\marketing\Model_Opportunity"] = ['All'=>'fa fa-globe','Open'=>"fa fa-lightbulb-o xepan-effect-yellow",'Converted'=>'fa fa-check text-success','Rejected'=>'fa fa-times text-danger'];
        $this->app->status_icon["xepan\marketing\Model_Newsletter"] = ['All'=>'fa fa-globe','Draft'=>"fa fa-sticky-note-o ",'Submitted'=>'fa fa-check-square-o text-primary','Approved'=>'fa fa-thumbs-up text-success','Rejected'=>'fa fa-times text-danger'];
        $this->app->status_icon["xepan\marketing\Model_SocialPost"] = ['All'=>'fa fa-globe','Draft'=>"fa fa-sticky-note-o ",'Submitted'=>'fa fa-check-square-o text-primary','Approved'=>'fa fa-thumbs-up text-success','Rejected'=>'fa fa-times text-danger'];
        $this->app->status_icon["xepan\marketing\Model_Sms"] = ['All'=>'fa fa-globe','Draft'=>"fa fa-sticky-note-o ",'Submitted'=>'fa fa-check-square-o text-primary','Approved'=>'fa fa-thumbs-up text-success','Rejected'=>'fa fa-times text-danger'];
        $this->app->status_icon["xepan\marketing\Model_Campaign"] = ['All'=>'fa fa-globe','Draft'=>"fa fa-sticky-note-o ",'Submitted'=>'fa fa-check-square-o text-primary','Redesign'=>'fa fa-refresh ','Approved'=>'fa fa-thumbs-up text-success','Onhold'=>'fa fa-pause text-warning'];
		$search_lead = $this->add('xepan\marketing\Model_Lead');
		$this->app->addHook('quick_searched',[$search_lead,'quickSearch']);
		$this->app->addHook('contact_save',[$this,'contactSave']);
		$this->app->addHook('widget_collection',[$this,'exportWidgets']);
		return $this;
	}

	function exportWidgets($app,&$array){
        $array['widget_list'][] = 'xepan\marketing\Widget_DayByDayCommunication';

    }
	
	function contactSave($app,$m){
		if($m->id == $this->app->employee->id)
			return;
		
		// finding id of marketing category
		$marketing_category = $this->add('xepan\marketing\Model_MarketingCategory');
		$marketing_category->tryLoadBy('name',$m['status'].' '.$m['type']);

		if(!$marketing_category->loaded())
			return;

		$active_category = $this->add('xepan\marketing\Model_MarketingCategory');
		$active_category->tryLoadBy('name','Active '.$m['type']);
		$active_cat_id = $active_category->id;  
		
		$inactive_category = $this->add('xepan\marketing\Model_MarketingCategory');
		$inactive_category->tryLoadBy('name','InActive '.$m['type']);
		$inactive_cat_id = $inactive_category->id;


		// finding current association 
		$cat_assoc = $this->add('xepan\marketing\Model_Lead_category_Association');
		$cat_assoc->addCondition('lead_id',$m->id);
		$cat_assoc->addCondition('marketing_category_id',[$active_cat_id,$inactive_cat_id]);

		// deleting current association 
		if($cat_assoc->count()->getOne() > 0){									
			foreach ($cat_assoc as $association_row){
				$association_row->delete();
			}
		}

		// new association 		
		$category_association_m = $this->add('xepan\marketing\Model_Lead_category_Association');
		$category_association_m['lead_id'] = $m->id;
		$category_association_m['marketing_category_id'] = $marketing_category->id;
		$category_association_m->save();		
	}

	function setup_frontend(){
		$this->routePages('xepan_marketing');
		$this->addLocation(array('template'=>'templates','js'=>'templates/js'))
		->setBaseURL('./vendor/xepan/marketing/');

		$this->app->addHook('cron_executor',function($app){
			
			$now = \DateTime::createFromFormat('Y-m-d H:i:s', $this->app->now);
			echo "Testing in Marketing<br/>";
			var_dump($now);

			$job2 = new \Cron\Job\ShellJob();
			$job2->setSchedule(new \Cron\Schedule\CrontabSchedule('* * * * *'));
			if(!$job2->getSchedule() || $job2->getSchedule()->valid($now)){	
				echo " Executing Newsletter exec <br/>";
				$this->add('xepan\marketing\Controller_NewsLetterExec');
			}

			$job3 = new \Cron\Job\ShellJob();
			$job3->setSchedule(new \Cron\Schedule\CrontabSchedule('* * * * *'));
			if(!$job3->getSchedule() || $job3->getSchedule()->valid($now)){	
				echo " Executing Social exec <br/>";
				$this->add('xepan\marketing\Controller_SocialExec');
			}

		});

		if($this->app->isEditing){
			
			$this->app->exportFrontEndTool('xepan\marketing\Tool_Subscription','Marketing');
		}

		return $this;
	}

	function resetDB(){
		$category_name = ['Default',
					 'Active Affiliate',
					 'InActive Affiliate',
					 'Active Employee',
					 'InActive Employee',
					 'Active Customer',
					 'InActive Customer',
					 'Active Supplier',
					 'InActive Supplier',
					 'Active OutSourceParty',
					 'InActive OutSourceParty'
					];

       	
       	foreach ($category_name as $cat) {
        	$mar_cat=$this->add('xepan\marketing\Model_MarketingCategory');
        	$mar_cat['name'] = $cat;
        	$mar_cat['system'] = true;
        	$mar_cat->save(); 
       	}

        $news=$this->add('xepan\marketing\Model_Newsletter');
        $news['marketing_category_id']=$mar_cat->id;
        $news['message_160']="No Content";
        $news['message_255']="No Content";
        $news['message_3000']="No Content";
        $news['message_blog']="No Content";
        $news['url']="xavoc.com";
        $news['title']="Empty";
        $news['is_template']=true;

        $news->save();
        // Create default Company Department
	}
}
