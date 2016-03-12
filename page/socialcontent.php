<?php
namespace xepan\marketing;
class page_socialcontent extends \Page{
	public $title="Social Content";
	function init(){
		parent::init();
		
		$social = $this->add('xepan\marketing\Model_SocialPost');
		$crud=$this->add('xepan\hr\CRUD',['action_page'=>'xepan_marketing_addsocialpost'],null,['grid/social-grid']);
		$crud->setModel($social);
		$frm=$crud->grid->addQuickSearch(['title']);
		$dropdown = $frm->addField('dropdown','status')->setValueList(['Draft'=>'Draft','Submitted'=>'Submitted','Approved'=>'Approved','Rejected'=>'Rejected'])->setEmptyText('Status');

		$dropdown->js('change',$frm->js()->submit());
	}
}