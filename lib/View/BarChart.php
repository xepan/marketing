<?php


namespace xepan\marketing;

class View_BarChart extends \View{
	function init(){
		parent::init();
	}

	function render(){
		return parent::render();
	}

	function getJSID(){
		return "graph-bar";
	}

	function defaultTemplate(){
		return ['view/barchart'];
	}
}