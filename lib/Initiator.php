<?php

namespace xepan\marketing;

class Initiator extends \Controller_Addon {
	public $addon_name = 'xepan_marketing';

	function setup_admin(){

		$this->routePages('xepan_marketing');
		$this->addLocation(array('template'=>'templates','js'=>'templates/js'))
		->setBaseURL('../vendor/xepan/marketing/');

		$m = $this->app->top_menu->addMenu('Marketing');
		$m->addItem(['Dashboard','icon'=>'fa fa-dashboard'],'xepan_marketing_dashboard');
		$m->addItem(['Category Management','icon'=>'fa fa-sitemap'],'xepan_marketing_marketingcategory');
		$m->addItem(['Lead','icon'=>'fa fa-users'],'xepan_marketing_lead');
		$m->addItem(['Opportunity','icon'=>'fa fa-user'],$this->api->url('xepan_marketing_opportunity',['status'=>'Open']));
		$m->addItem(['Newsletter','icon'=>'fa fa-envelope-o'],'xepan_marketing_newsletter');
		$m->addItem(['Social Marketing','icon'=>'fa fa-globe'],'xepan_marketing_socialcontent');
		$m->addItem(['Tele Marketing','icon'=>'fa fa-phone'],'xepan_marketing_telemarketing');
		$m->addItem(['SMS','icon'=>'fa fa-envelope-square'],'xepan_marketing_sms');
		$m->addItem(['Campaign','icon'=>'fa fa-bullhorn'],'xepan_marketing_campaign');
		$m->addItem(['Reports','icon'=>'fa fa-bar-chart-o'],'xepan_marketing_reports');
		$m->addItem(['Social Config','icon'=>'fa fa-bar-chart-o'],'xepan_marketing_socialconfiguration');
		$m->addItem(['Social Exec','icon'=>'fa fa-bar-chart-o'],'xepan_marketing_socialexec');
	
		return $this;
		
	}

	function setup_frontend(){
		$this->routePages('xepan_marketing');
		$this->addLocation(array('template'=>'templates','js'=>'templates/js'))
		->setBaseURL('./vendor/xepan/marketing/');
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
        $news->save();
        // Create default Company Department
	}
}
