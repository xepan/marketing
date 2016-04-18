<?php

namespace xepan\marketing;

class Initiator extends \Controller_Addon {
	public $addon_name = 'xepan_marketing';

	function init(){
		parent::init();
		
		$this->routePages('xepan_marketing');
		$this->addLocation(array('template'=>'templates','js'=>'templates/js'))
		->setBaseURL('../vendor/xepan/marketing/');

		if($this->app->is_admin){
			$m = $this->app->top_menu->addMenu('Marketing');
			$m->addItem(['Category Management','icon'=>'fa fa-table'],'xepan_marketing_marketingcategory');
			$m->addItem(['Lead','icon'=>'fa fa-desktop'],'xepan_marketing_lead');
			$m->addItem(['Opportunity','icon'=>'fa fa-archive'],'xepan_marketing_opportunity');
			$m->addItem(['Newsletter','icon'=>'fa fa-copy'],'xepan_marketing_newsletter');
			$m->addItem(['Social','icon'=>'fa fa-cubes'],'xepan_marketing_socialcontent');
			$m->addItem(['SMS','icon'=>'fa fa-envelope'],'xepan_marketing_sms');
			$m->addItem(['Campaign','icon'=>'fa fa-sliders'],'xepan_marketing_campaign');
			$m->addItem(['Report','icon'=>'fa fa-file-text-o'],'xepan_marketing_report');
		}
		
	}

	function generateInstaller(){
		// Clear DB
		if(!isset($this->app->old_epan)) $this->app->old_epan = $this->app->epan;
        if(!isset($this->app->new_epan)) $this->app->new_epan = $this->app->epan;
        $this->app->epan=$this->app->old_epan;
        $truncate_models = ['Opportunity','Lead_Category_Association','Lead','Campaign_Category_Association','Schedule','Campaign_SocialUser_Association','SocialUser','campaign','Content','MarketingCategory'];
        foreach ($truncate_models as $t) {
            $m=$this->add('xepan\marketing\Model_'.$t);
            foreach ($m as $mt) {
                $mt->delete();
            }
        }
        
        $this->app->epan=$this->app->new_epan;

        // Create default Company Department
	}
}
