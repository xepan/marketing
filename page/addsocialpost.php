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
		
		$sv->js(true)->_load('jquery.sparkline.min')->_selector('.sparkline')->sparkline('html', ['enableTagOptions' => true]);
	    
	    $sv->setModel($social,['title','message_blog','url','marketing_category_id'],['title','message_blog','url','marketing_category_id']);
    	if($_GET['document_id']){
	    	$model_attachment = $this->add('xepan\base\Model_Document_Attachment')->addCondition('document_id',$_GET['document_id']);
	    	$model_attachment->acl = 'xepan\marketing\Model_SocialPost';
    		$attachment = $sv->addMany('Attachment',null,'attachment',['view/socialimage']);
			$attachment->setModel($model_attachment);
    	}

    	$sv->form->onSubmit(function($f){
    		$f->save();
    		return $this->js()->univ()->redirect($this->app->url('xepan_marketing_addsocialpost',['action'=>'edit','document_id'=>$f->model->id]));	
    	});
	}
}