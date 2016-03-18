<?php

namespace xepan\marketing;

class View_ScheduleContent extends \CompleteLister{
	function init(){
		parent::init();

	}

	function defaultTemplate(){
		return['view/schedulecontent'];
	}

	function render(){
		$this->js(true)->_selector('.draggable-event')->draggable(array( 'helper'=> 'clone'));
		parent::render();
	}
}