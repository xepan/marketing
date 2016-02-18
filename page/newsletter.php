<?php
namespace xepan\marketing;
class page_newsletter extends \Page{
	public $title="Newsletter";
	function init(){
		parent::init();

		$this->add('View_Marketing_progressbar',null,'progressbar');
		$submitted = $this->add('View_Marketing_newsletter',['status'=>'submitted'],'newsletter');

		$this->add('View_Marketing_newsletter',null,'newsletter');
		$this->add('View_Marketing_newsletter',null,'newsletter');
		$this->add('View_Marketing_newsletter',null,'newsletter');
		$this->add('View_Marketing_newsletter',null,'newsletter');
		$this->add('View_Marketing_newsletter',null,'newsletter');
		

	}

	function defaultTemplate(){

		return ['page/newsletter'];
	}
}