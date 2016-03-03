<?php
namespace xepan\marketing;
class page_schedule extends \Page{
	public $title="Schedule";
	function init(){
		parent::init();	

		$action = $this->api->stickyGET('action')?:'view';
		$schedule = $this->add('xepan\marketing\Model_Sms')->tryLoadBy('id',$this->api->stickyGET('document_id'));

		$sch = $this->add('xepan\hr\View_Document',['action'=>$action],null,['view/schedule']);
	    $sch->setModel($schedule);
	}
}