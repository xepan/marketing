<?php
namespace xepan\marketing;
class page_newsletterdesign extends \xepan\base\Page{
	public $title="Newsletter Design";
	public $breadcrumb=['Home'=>'index','Newsletter'=>'xepan_marketing_newslettertemplate','Design'=>'#'];

	function init(){
		parent::init();	

		$action = $this->api->stickyGET('action')?:'view';
		$newsletter = $this->add('xepan\marketing\Model_Newsletter')->tryLoadBy('id',$this->api->stickyGET('document_id'));

		$nv = $this->add('xepan\hr\View_Document',['action'=>$action,'id_field_on_reload'=>'content_id'],null,['view/newsletterdesign']);
		$nv->setModel($newsletter,['title','message_blog','marketing_category','created_by','created_at'],['title','message_blog','marketing_category_id']);
	}
}