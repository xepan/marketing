<?php
namespace xepan\marketing;
class page_addsocialpost extends \Page{
	public $title="Add Social Post";
	function init(){
		parent::init();	

		$action = $this->api->stickyGET('action')?:'view';
		$social = $this->add('xepan\marketing\Model_SocialPost')->tryLoadBy('id',$this->api->stickyGET('document_id'));

		$sv = $this->add('xepan\hr\View_Document',['action'=>$action,'id_field_on_reload'=>'content_id'],null,['view/addsocialpost']);

	    $sv->setModel($social,['title','message_160','message_255','message_3000','message_blog','attachment','url','marketing_category_id'],['title','message_160','message_255','message_3000','message_blog','attachment','url','marketing_category_id']);
	}
}