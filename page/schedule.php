<?php
namespace xepan\marketing;

class page_schedule extends \Page{
	
	public $title="Schedule";
	
	function init(){
		parent::init();	

		$m=$this->add('xepan/marketing/Model_Campaign');

		// $content_view = $this->add('View_ScheduleContent',null,'MarketingContent');
		// $content_view->setModel('xepan\marketing\Content');
	}

	function defaultTemplate(){
		return['page/schedule'];
	}

	function render(){

		$this->app->jquery->addStylesheet('libs/fullcalendar');
		$this->app->jquery->addStylesheet('libs/fullcalendar.print');
		$this->app->jquery->addStylesheet('compiled/calendar');

		$this->js(true)->_load('fullcalendar.min')->_load('xepan-scheduler');
		$this->js(true)->_selector('#calendar')->univ()->schedularDate([
				[
					'title'=> 'All Day Event',
					'start'=> date('Y-m-d'),
					'className'=> 'label-success'
				]
			]);
		parent::render();

	}
}