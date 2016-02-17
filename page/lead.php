<?php

class page_marketing_lead extends Page{

	function init(){
		parent::init();

		$this->add('View_Info')->set('hello');
	}

	function defaultTemplate(){

		return ['page/marketing/lead'];
	}
}