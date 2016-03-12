<?php
namespace xepan\marketing;
class page_addcampaign extends \Page{
	public $title="Add Campaign";
	function init(){
		parent::init();	

		$action = $this->api->stickyGET('action')?:'view';
		$campaign = $this->add('xepan\marketing\Model_Campaign')->tryLoadBy('id',$this->api->stickyGET('document_id'));

		$camp = $this->add('xepan\hr\View_Document',['action'=>$action,'id_field_on_reload'=>'campaign_id'],null,['view/addcampaign']);
	    $camp->setModel($campaign,['title', 'starting_date', 'ending_date', 'campaign_category', 'campaign_type'],['title', 'starting_date', 'ending_date', 'campaign_category_id', 'campaign_type']);
	}
}