<?php
namespace xepan\marketing;

class View_newsletter extends \View{
	public $status = "draft";
	function init(){
		parent::init();

		
		if($this->status == "submitted"){
			$this->add('Button',null,'test_button')->set('Approve')->setClass('btn btn-warning');
		}else
			$this->add('Button',null,'test_button')->set('Test')->setClass('btn btn-primary');

	}

	function render(){

		parent::render();
	}

	function defaultTemplate(){

		return ['view/newsletter'];
	}

}