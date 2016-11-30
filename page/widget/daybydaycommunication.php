<?php

namespace xepan\marketing;

class page_widget_daybydaycommunication extends \xepan\base\Page{
	function init(){
		parent::init();

		$x_axis = $this->app->stickyGET('x_axis');
		$details = $this->app->stickyGET('details');
		
		throw new \Exception($x_axis."---".$details);
	}
}