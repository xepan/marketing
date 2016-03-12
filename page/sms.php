<?php
namespace xepan\marketing;
class page_sms extends \Page{
	public $title="SMS";
	function init(){
		parent::init();

		$sms = $this->add('xepan\marketing\Model_Sms');
		$crud=$this->add('xepan\hr\CRUD',['action_page'=>'xepan_marketing_addsms'],null,['grid/sms-grid']);
		$crud->setModel($sms);
		$crud->grid->addQuickSearch(['title']);
	}
}