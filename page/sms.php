<?php
namespace xepan\marketing;
class page_sms extends \Page{

	function init(){
		parent::init();

		$this->add('View_Marketing_progressbar',null,'progressbar');
		$this->add('View_Marketing_sms',['status'=>'submitted'],'sms');
		$this->add('View_Marketing_sms',null,'sms');
		$this->add('View_Marketing_sms',null,'sms');
		$this->add('View_Marketing_sms',null,'sms');
		
	}

	function defaultTemplate(){

		return['page/marketing/sms'];
	}
}