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
		$model_landingresponse['type'] = "Newsletter Response";
		$model_landingresponse['referrersite'] = $_GET['xepan_landing_referersite']?:$_SERVER['HTTP_REFERER'];
		$model_landingresponse['ip'] = $_SERVER['REMOTE_ADDR'];
		$model_landingresponse->save();
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
		$m->addItem(['Opportunity','icon'=>'fa fa-user'],$this->api->url('xepan_marketing_opportunity',['status'=>'Open']));
		$m->addItem(['Newsletter','icon'=>'fa fa-envelope-o'],$this->app->url('xepan_marketing_newsletter',['status'=>'Draft,Submitted,Approved']));
		$m->addItem(['Social Content','icon'=>'fa fa-globe'],$this->app->url('xepan_marketing_socialcontent',['status'=>'Draft,Submitted,Approved']));
		$m->addItem(['Tele Marketing','icon'=>'fa fa-phone'],'xepan_marketing_telemarketing');
		$m->addItem(['SMS','icon'=>'fa fa-envelope-square'],$this->app->url('xepan_marketing_sms',['status'=>'Draft,Submitted,Approved']));
		$m->addItem(['Campaign','icon'=>'fa fa-bullhorn'],$this->app->url('xepan_marketing_campaign',['status'=>'Draft,Submitted,Redesign,Approved,Onhold']));
		$m->addItem(['Reports','icon'=>'fa fa-bar-chart-o'],'xepan_marketing_reports');
		$m->addItem(['Social Config','icon'=>'fa fa-bar-chart-o'],'xepan_marketing_socialconfiguration');
		$m->addItem(['Social Exec','icon'=>'fa fa-bar-chart-o'],'xepan_marketing_socialexec');

		
        $this->app->status_icon["xepan\marketing\Model_Lead"] = ['All'=>'fa fa-globe','Active'=>"fa fa-circle text-success",'InActive'=>'fa fa-circle text-danger'];
        $this->app->status_icon["xepan\marketing\Model_Opportunity"] = ['All'=>'fa fa-globe','Open'=>"fa fa-lightbulb-o xepan-effect-yellow",'Converted'=>'fa fa-check text-success','Rejected'=>'fa fa-times text-danger'];
        $this->app->status_icon["xepan\marketing\Model_Newsletter"] = ['All'=>'fa fa-globe','Draft'=>"fa fa-sticky-note-o ",'Submitted'=>'fa fa-check-square-o text-primary','Approved'=>'fa fa-thumbs-up text-success','Rejected'=>'fa fa-times text-danger'];
        $this->app->status_icon["xepan\marketing\Model_SocialPost"] = ['All'=>'fa fa-globe','Draft'=>"fa fa-sticky-note-o ",'Submitted'=>'fa fa-check-square-o text-primary','Approved'=>'fa fa-thumbs-up text-success','Rejected'=>'fa fa-times text-danger'];
        $this->app->status_icon["xepan\marketing\Model_Sms"] = ['All'=>'fa fa-globe','Draft'=>"fa fa-sticky-note-o ",'Submitted'=>'fa fa-check-square-o text-primary','Approved'=>'fa fa-thumbs-up text-success','Rejected'=>'fa fa-times text-danger'];
        $this->app->status_icon["xepan\marketing\Model_Campaign"] = ['All'=>'fa fa-globe','Draft'=>"fa fa-sticky-note-o ",'Submitted'=>'fa fa-check-square-o text-primary','Redesign'=>'fa fa-refresh ','Approved'=>'fa fa-thumbs-up text-success','Onhold'=>'fa fa-pause text-warning'];
		$search_lead = $this->add('xepan\marketing\Model_Lead');
		$this->app->addHook('quick_searched',[$search_lead,'quickSearch']);
		return $this;
		
	}

	function setup_frontend(){
		$this->routePages('xepan_marketing');
		$this->addLocation(array('template'=>'templates','js'=>'templates/js'))
		->setBaseURL('./vendor/xepan/marketing/');

		$this->app->addHook('cron_exector',function($app,$resolver){

			$job2 = new \Cron\Job\ShellJob();
			$job2->setCommand('wget http://'.$this->app->current_website_name.'.epan.in/?page=xepan_communication_cron');
			$job2->setSchedule(new \Cron\Schedule\CrontabSchedule('*/5 * * * *'));

			$job3 = new \Cron\Job\ShellJob();
			$job3->setCommand('wget http://'.$this->app->current_website_name.'.epan.in/?page=xepan_communication_cron');
			$job3->setSchedule(new \Cron\Schedule\CrontabSchedule('*/5 * * * *'));

			$resolver->addJob($job2);
			$resolver->addJob($job3);
		});

		return $this;
	}

	function resetDB(){
		// Clear DB
		if(!isset($this->app->old_epan)) $this->app->old_epan = $this->app->epan;
        if(!isset($this->app->new_epan)) $this->app->new_epan = $this->app->epan;
        $this->app->epan=$this->app->old_epan;
        $truncate_models = ['Opportunity','Lead_Category_Association','Lead','Campaign_Category_Association','Schedule','Campaign_SocialUser_Association','campaign','Content','MarketingCategory'];
        foreach ($truncate_models as $t) {
            $m=$this->add('xepan\marketing\Model_'.$t);
            foreach ($m as $mt) {
                $mt->delete();
            }
        }
        
        $this->app->epan=$this->app->new_epan;

        $mar_cat=$this->add('xepan\marketing\Model_MarketingCategory');
        $mar_cat['name']="default";
        $mar_cat->save();

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
