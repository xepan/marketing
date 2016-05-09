<?php

namespace xepan\marketing;

class page_newsletterpreview extends \xepan\base\Page{
	function init(){
		parent::init();

		$m=$this->add('xepan/marketing/Model_Content');
		$m->load($_GET['content_id']);
		$this->add('View')->setHtml($m['message_blog']);
	}
}