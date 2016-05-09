<?php
namespace xepan\marketing;
class page_newmailconfig extends \xepan\base\Page{
	public $title="Mail Configuration";
	function init(){
		parent::init();

		$this->add('xepan\marketing\View_transportsettings');
		$this->add('xepan\marketing\View_logincredintals');
		$this->add('xepan\marketing\View_headersettings');
		$this->add('xepan\marketing\View_throttling');

	}

	function defaultTemplate(){
		return ['page/newmailconfig'];
	}
}