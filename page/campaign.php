<?php
namespace xepan\marketing;
class page_campaign extends \Page{
	public $title="Campaign";
	function init(){
		parent::init();	

		$campaign = $this->add('xepan\marketing\Model_Campaign');
		$crud=$this->add('xepan\hr\CRUD',['action_page'=>'xepan_marketing_addcampaign'],null,['grid/campaign-grid']);
		$crud->setModel($campaign);

	}
}