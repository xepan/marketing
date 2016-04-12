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
}
