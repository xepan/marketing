<?php

namespace xepan\marketing;

class View_ScheduleContent extends \CompleteLister{
	function init(){
		parent::init();		
	}

	function formatRow(){
		parent::formatRow();

		if($this->model['type'] == 'Newsletter'){
			$this->current_row_html['icon'] = 'fa fa-envelope';
		}elseif($this->model['type'] == 'SocialPost'){
			$this->current_row_html['icon'] = 'fa fa-share-alt';
		}else{
			$this->current_row_html['icon'] = 'fa fa-cog';
		}
	}

	function defaultTemplate(){
		return['view/schedulecontent'];
	}

	function render(){
		$this->js(true)->_selector('.draggable-event')->draggable(array( 'helper'=> 'clone'));
		parent::render();
	}
}