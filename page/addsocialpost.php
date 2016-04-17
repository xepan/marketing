<?php
namespace xepan\marketing;
class page_addsocialpost extends \xepan\base\Page{
	public $title="Add Social Post";
	public $breadcrumb=['Home'=>'index','Social'=>'xepan_marketing_socialcontent','addsocialpost'=>'#'];

	function init(){
		parent::init();	

		$action = $this->api->stickyGET('action')?:'view';
		$social = $this->add('xepan\marketing\Model_SocialPost')->tryLoadBy('id',$this->api->stickyGET('document_id'));

		$sv = $this->add('xepan\hr\View_Document',['action'=>$action,'id_field_on_reload'=>'document_id'],null,['view/addsocialpost']);
		
	    $sv->setModel($social,['title','message_160','message_255','message_3000','message_blog','url','marketing_category_id'],['title','message_160','message_255','message_3000','message_blog','url','marketing_category_id']);

	    $model_attachment = $this->add('xepan\base\Model_Document_Attachment')->addCondition('document_id',$_GET['document_id']);
	    $attachment = $sv->addMany('Attachment',null,'attachment',null);
		$attachment->setModel($model_attachment);

	}
}