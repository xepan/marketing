<?php

class page_marketing_newmailconfig extends Page{

	function init(){
		parent::init();

		$this->add('View_Marketing_transportsettings');
		$this->add('View_Marketing_logincredintals');
		$this->add('View_Marketing_headersettings');
		$this->add('View_Marketing_throttling');

	}

	function defaultTemplate(){
		return ['page/marketing/newmailconfig'];
	}
}