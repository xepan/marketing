<?php
namespace xepan\marketing;
class page_campaign extends \Page{
	public $title="Campaign";
	function init(){
		parent::init();	

		$campaign = $this->add('xepan\marketing\Model_Campaign');
		$crud=$this->add('xepan\hr\CRUD',['action_page'=>'xepan_marketing_addcampaign'],null,['grid/campaign-grid']);
		$crud->setModel($campaign);

		$frm=$crud->grid->addQuickSearch(['title']);
		$dropdown = $frm->addField('dropdown','status')->setValueList(['Draft'=>'Draft','Submitted'=>'Submitted','Redesign'=>'Redesign','Approved'=>'Approved','Onhold'=>'Onhold'])->setEmptyText('Status');

		$dropdown->js('change',$frm->js()->submit());

	}
}