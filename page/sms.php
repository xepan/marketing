<?php
namespace xepan\marketing;
class page_sms extends \Page{
	public $title="SMS";
	function init(){
		parent::init();

		$sms = $this->add('xepan\marketing\Model_Sms');
		$crud=$this->add('xepan\hr\CRUD',['action_page'=>'xepan_marketing_addsms'],null,['grid/sms-grid']);
		$crud->setModel($sms);
		
		$frm=$crud->grid->addQuickSearch(['title']);
		$dropdown = $frm->addField('dropdown','status')->setValueList(['Draft'=>'Draft','Submitted'=>'Submitted','Approved'=>'Approved','Rejected'=>'Rejected'])->setEmptyText('Status');

		$dropdown->js('change',$frm->js()->submit());
	}
}