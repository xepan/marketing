<?php

/**
* description: ATK Page
* 
* @author : Gowrav Vishwakarma
* @email : gowravvishwakarma@gmail.com, info@xavoc.com
* @website : http://xepan.org
* 
*/

namespace xepan\marketing;

class page_newsletterexec extends \Page {
	
	public $title='Cron to send NewsLetters';
	public $debug = false;

	function init(){
		parent::init();
		$this->add('xepan\marketing\Controller_NewsLetterExec');
	}
}