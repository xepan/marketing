<?php

namespace xepan\marketing;

class View_ScheduleContent extends \CompleteLister{
	function init(){
		parent::init();

	}

	function defaultTemplate(){
		return['view/schedulecontent'];
	}
}