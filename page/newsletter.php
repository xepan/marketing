<?php
namespace xepan\marketing;
class page_newsletter extends \Page{
	public $title="Newsletter";
	function init(){
		parent::init();

		$newsletter = $this->add('xepan\marketing\Model_Newsletter');
		$newsletter->addCondition('is_template',false);
		$crud=$this->add('xepan\hr\CRUD',['action_page'=>'xepan_marketing_newslettertemplate', 'edit_page'=>'xepan_marketing_newsletterdesign'],null,['grid/newsletter-grid']);
		$crud->grid->addQuickSearch(['title']);
		$crud->setModel($newsletter);
	}
}