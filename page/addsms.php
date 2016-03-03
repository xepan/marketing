<?php
namespace xepan\marketing;
class page_addsms extends \Page{
	public $title="Add Sms";
	function init(){
		parent::init();	

		$action = $this->api->stickyGET('action')?:'view';
		$sms = $this->add('xepan\marketing\Model_Sms')->tryLoadBy('id',$this->api->stickyGET('document_id'));

		$sv = $this->add('xepan\hr\View_Document',['action'=>$action],null,['view/addsms']);
	    $sv->setModel($sms,['title','message_160','marketing_category_id'],['title','message_160','marketing_category_id']);
	}
}