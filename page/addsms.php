<?php
namespace xepan\marketing;
class page_addsms extends \xepan\base\Page{
	public $title="Add Sms";
	public $breadcrumb=['Home'=>'index','Sms'=>'xepan_marketing_sms','Add sms'=>'#'];

	function init(){
		parent::init();	

		$action = $this->api->stickyGET('action')?:'view';
		$sms = $this->add('xepan\marketing\Model_Sms')->tryLoadBy('id',$this->api->stickyGET('document_id'));

		$sv = $this->add('xepan\hr\View_Document',['action'=>$action,'id_field_on_reload'=>'content_id'],null,['view/addsms']);
	    $sv->setModel($sms,['title','message_blog','marketing_category_id'],['title','message_blog','marketing_category_id']);
	
	    $sv->form->onSubmit(function($f){
    		$f->save();
    		return $this->js()->univ()->redirect($this->app->url('xepan_marketing_sms'));	
    	});
	}
}