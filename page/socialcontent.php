<?php
namespace xepan\marketing;
class page_socialcontent extends \xepan\base\Page{
	public $title="Social Content";
	function init(){
		parent::init();
		

		$social = $this->add('xepan\marketing\Model_SocialPost');
		if($this->app->stickyGET('status'))
			$social->addCondition('status',explode(",",$this->app->stickyGET('status')));
				
		$social->add('xepan\hr\Controller_SideBarStatusFilter');
		
		$crud=$this->add('xepan\hr\CRUD',['action_page'=>'xepan_marketing_addsocialpost'],null,['grid/social-grid']);
		$crud->setModel($social);
		$frm=$crud->grid->addQuickSearch(['title']);
	}
}