<?php
namespace xepan\marketing;
class page_newslettertemplate extends \Page{
	public $title="Newsletter Template";
	function init(){
		parent::init();	

		//$action = $this->api->stickyGET('action')?:'view';
		$newsletter = $this->add('xepan\marketing\Model_Newsletter');
		$newsletter->addCondition('is_template',true);
		$crud=$this->add('xepan\base\Grid',null,null,['grid/newslettertemplate-grid']);
		//$crud = $this->add('xepan\hr\View_Document',['action'=> $action],null,['view/newsletterdesign']);
		$crud->setModel($newsletter);
	}
}