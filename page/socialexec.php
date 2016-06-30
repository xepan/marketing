<?php
namespace xepan\marketing;

class page_socialexec extends \Page{

	function init(){
		parent::init();
		$this->add('xepan\marketing\Controller_SocialExec');
	}
}