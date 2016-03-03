<?php
namespace xepan\marketing;
class page_socialcontent extends \Page{
	public $title="Social Content";
	function init(){
		parent::init();
		
		$social = $this->add('xepan\marketing\Model_SocialPost');
		$crud=$this->add('xepan\hr\CRUD',['action_page'=>'xepan_marketing_addsocialpost'],null,['grid/social-grid']);
		$crud->setModel($social);
	}
}