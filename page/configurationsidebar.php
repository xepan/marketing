<?php
namespace xepan\marketing;
class page_configurationsidebar extends \xepan\base\Page{
	function init(){
		parent::init();
		$this->app->side_menu->addItem(['Social Configuration','icon'=>'fa fa-globe'],'xepan_marketing_socialconfiguration')->setAttr(['title'=>'Social Configuration']);
		$this->app->side_menu->addItem(['Lead Source','icon'=>'fa fa-user'],'xepan_marketing_leadsource')->setAttr(['title'=>'Lead Source']);
		$this->app->side_menu->addItem(['External Configuration','icon'=>'fa fa-gears'],'xepan_marketing_externalconfiguration')->setAttr(['title'=>'External Configuration']);
	}
}