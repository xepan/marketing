<?php
namespace xepan\marketing;
class page_sms extends \xepan\base\Page{
	public $title="SMS";
	function init(){
		parent::init();


		$sms = $this->add('xepan\marketing\Model_Sms');
		if($this->app->stickyGET('status'))
			$sms->addCondition('status',explode(",",$this->app->stickyGET('status')));
				
		$sms->add('xepan\hr\Controller_SideBarStatusFilter');
		$crud=$this->add('xepan\hr\CRUD',['action_page'=>'xepan_marketing_addsms'],null,['grid/sms-grid']);
		$crud->setModel($sms);
		
		$frm=$crud->grid->addQuickSearch(['title']);
	}
}