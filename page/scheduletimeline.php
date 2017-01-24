<?php

namespace xepan\marketing;

class page_scheduletimeline extends \xepan\base\Page{
	public $title = "Schedule Timeline";

	function init(){
		parent::init();

		$timeline_m = $this->add('xepan\marketing\Model_Campaign_ScheduleTimeline');
		$timeline_m->addCondition('sendable',true);
		$timeline_m->addCondition('campaign_status','Approved');
		$timeline_m->addCondition('content_status','Approved');
		$timeline_m->addCondition('is_already_sent',0);
		
		$grid = $this->add('xepan\base\Grid');
		$grid->setModel($timeline_m,['effective_name','emails_str','document','schedule_date','schedule_day','document_type','days_from_join']);
		$grid->addPaginator(50);
	}
}


// $day_array = [
// 						['date' =>$this->app->today." 11:59:59"],
// 						['date' =>date("Y-m-d", strtotime('+ 1 day', strtotime($this->app->now)))." 11:59:59"],
// 						['date' => date("Y-m-d", strtotime('+ 2 day', strtotime($this->app->now)))." 11:59:59"],
// 						['date' => date("Y-m-d", strtotime('+ 3 day', strtotime($this->app->now)))." 11:59:59"],
// 						['date' => date("Y-m-d", strtotime('+ 4 day', strtotime($this->app->now)))." 11:59:59"],
// 						['date' => date("Y-m-d", strtotime('+ 5 day', strtotime($this->app->now)))." 11:59:59"],
// 						['date' => date("Y-m-d", strtotime('+ 6 day', strtotime($this->app->now)))." 11:59:59"]
// 					];

// 		$rows = [];			
// 		foreach ($day_array as $date) {					
// 			$model = $this->add('xepan\marketing\Model_Campaign_ScheduleTimeline',['on_time'=>$date['date']]);
// 			$model->addCondition('campaign_status','Approved');
// 			$model->addCondition('content_status','Approved');
// 			$model->addCondition('is_already_sent',0);
// 			$model->addCondition('sendable',true);
// 			$model->addCondition('document_type','Newsletter');		
// 			$rows[] = $model->getRows(); 
// 		}

// 		// echo "<pre>";
// 		// print_r($day_array);
// 		// echo "</pre>";
// 		// exit;

// 		asort($day_array);
// 		$grid = $this->add('Grid');
// 		$grid->setSource($day_array);
// 		$grid->addColumn('date');
// 		$grid->removeColumn('id');
// 		// $grid->addPaginator(50);