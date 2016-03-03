<?php

namespace xepan\marketing;

class Initiator extends \Controller_Addon {
	public $addon_name = 'xepan_marketing';

	function init(){
		parent::init();
		
		$this->routePages('xepan_marketing');
		$this->addLocation(array('template'=>'templates'));

		if($this->app->is_admin){
			$m = $this->app->top_menu->addMenu('Marketing');
			$m->addItem('Category Management','xepan_marketing_marketingcategory');
			$m->addItem('Lead','xepan_marketing_lead');
			$m->addItem('Opportunity','xepan_marketing_opportunity');
			$m->addItem('Newsletter','xepan_marketing_newsletter');
			$m->addItem('Social','xepan_marketing_socialcontent');
			$m->addItem('SMS','xepan_marketing_sms');
			$m->addItem('Campaign','xepan_marketing_campaign');
			$m->addItem('Report','xepan_marketing_report');

		}
		
	}
}
