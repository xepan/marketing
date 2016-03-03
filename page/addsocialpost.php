<?php
namespace xepan\marketing;
class page_addsocialpost extends \Page{
	public $title="Add Social Post";
	function init(){
		parent::init();	

		$action = $this->api->stickyGET('action')?:'view';
		$social = $this->add('xepan\marketing\Model_SocialPost')->tryLoadBy('id',$this->api->stickyGET('document_id'));

		$sv = $this->add('xepan\hr\View_Document',['action'=>$action],null,['view/addsocialpost']);
		$sv->setModel($social,['title','short_content','marketing_category','created_by','created_at'],['title','short_content','marketing_category_id']);
	}
}