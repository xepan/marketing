<?php
namespace xepan\marketing;
class page_sms extends \Page{
	public $title="SMS";
	function init(){
		parent::init();
		
		$this->add('xepan\marketing\View_progressbar',null,'progressbar');
		$this->add('xepan\marketing\View_sms',['status'=>'submitted'],'sms');
		$this->add('xepan\marketing\View_sms',null,'sms');
		$this->add('xepan\marketing\View_sms',null,'sms');
		$this->add('xepan\marketing\View_sms',null,'sms');
		
	}

	function defaultTemplate(){

		return['page/sms'];
	}
}