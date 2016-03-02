<?php
namespace xepan\marketing;
class page_newsletterdesign extends \Page{
	public $title="Newsletter Design";
	function init(){
		parent::init();	

		$action = $this->api->stickyGET('action')?:'view';
		$newsletter = $this->add('xepan\marketing\Model_Newsletter')->tryLoadBy('id',$this->api->stickyGET('document_id'));

		$nv = $this->add('xepan\hr\View_Document',['action'=>$action],null,['view/newsletterdesign']);
		$nv->setModel($newsletter,['title','long_content','marketing_category','created_by','created_at'],['title','long_content','marketing_category_id']);
	}
}