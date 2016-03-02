<?php
namespace xepan\marketing;
class page_newsletter extends \Page{
	public $title="Newsletter";
	function init(){
		parent::init();

		// $this->add('xepan\marketing\View_progressbar',null,'progressbar');
		// $submitted = $this->add('xepan\marketing\View_newsletter',['status'=>'submitted'],'newsletter');

		// $this->add('xepan\marketing\View_newsletter',null,'newsletter');
		// $this->add('xepan\marketing\View_newsletter',null,'newsletter');
		// $this->add('xepan\marketing\View_newsletter',null,'newsletter');
		// $this->add('xepan\marketing\View_newsletter',null,'newsletter');
		// $this->add('xepan\marketing\View_newsletter',null,'newsletter');

		$newsletter = $this->add('xepan\marketing\Model_Newsletter');
		$newsletter->addCondition('is_template',false);
		$crud=$this->add('xepan\hr\CRUD',['action_page'=>'xepan_marketing_newslettertemplate', 'edit_page'=>'xepan_marketing_newsletterdesign'],null,['grid/newsletter-grid']);

		//$crud=$this->add('xepan\hr\CRUD',null,null,['grid/newsletter-grid']);
		$crud->setModel($newsletter);
	}

	// function defaultTemplate(){

	// 	return ['page/newsletter'];
	// }
}