<?php
namespace xepan\marketing;
class page_campaign extends \Page{
	public $title="Campaign";
	function init(){
		parent::init();	

		$campaign = $this->add('xepan\marketing\Model_Campaign');
		if($this->app->stickyGET('status'))
			$campaign->addCondition('status',explode(",",$this->app->stickyGET('status')));
				
		$campaign->add('xepan\hr\Controller_SideBarStatusFilter');
		$crud=$this->add('xepan\hr\CRUD',['action_page'=>'xepan_marketing_addcampaign'],null,['grid/campaign-grid']);
		$crud->setModel($campaign);

		$frm=$crud->grid->addQuickSearch(['title']);
	}
}