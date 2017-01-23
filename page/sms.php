<?php
namespace xepan\marketing;
class page_sms extends \xepan\base\Page{
	public $title="SMS";
	function init(){
		parent::init();


		$sms = $this->add('xepan\marketing\Model_Sms');
		if($this->app->stickyGET('status'))
			$sms->addCondition('status',explode(",",$this->app->stickyGET('status')));
				
		$sms->add('xepan\marketing\Controller_SideBarStatusFilter');
		$crud=$this->add('xepan\hr\CRUD',['action_page'=>'xepan_marketing_addsms'],null,['grid/sms-grid']);
		$crud->setModel($sms);
		
		$crud->grid->addPaginator('20');
		$frm=$crud->grid->addQuickSearch(['title']);

		$marketing_category = $frm->addField('DropDown','marketing_category_id');
		$marketing_category->setModel('xepan\marketing\Model_MarketingCategory');
		$marketing_category->setEmptyText('Select a category');

		$frm->addHook('applyFilter',function($f,$m){
			if($f['marketing_category_id']){
				$m->addCondition('marketing_category_id',$f['marketing_category_id']);
			}
		});
		
		$marketing_category->js('change',$frm->js()->submit());
	}
}