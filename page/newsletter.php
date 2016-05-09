<?php
namespace xepan\marketing;
class page_newsletter extends \xepan\base\Page{
	public $title="Newsletter";
	function init(){
		parent::init();

		$newsletter = $this->add('xepan\marketing\Model_Newsletter');

		if($this->app->stickyGET('status'))
			$newsletter->addCondition('status',explode(",",$this->app->stickyGET('status')));
				
		$newsletter->add('xepan\hr\Controller_SideBarStatusFilter');
		$newsletter->addCondition('is_template',false);
		$crud=$this->add('xepan\hr\CRUD',['action_page'=>'xepan_marketing_newslettertemplate', 'edit_page'=>'xepan_marketing_newsletterdesign'],null,['grid/newsletter-grid']);
		$crud->setModel($newsletter);
		
		$frm=$crud->grid->addQuickSearch(['title']);
	}
}