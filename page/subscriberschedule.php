<?php

namespace xepan\marketing;

class page_subscriberschedule extends \xepan\base\Page{
	public $title = 'Schedule';
	public $breadcrumb=['Home'=>'index','Campaign'=>'xepan_marketing_campaign','Schedule'=>'#'];
	
	function init(){
		parent::init();

		$campaign_id=$this->app->stickyGET('campaign_id');
		$m = $this->add('xepan/marketing/Model_Campaign')->load($campaign_id);

		// Setting oldest days from join
		if($m['campaign_type'] == 'subscription'){
			$this->template->trySet('day','['.'Todays Day : '.$m['todays_day'].']');
		}


		$content_view = $this->add('xepan/marketing/View_ScheduleContent',null,'MarketingContent');
		$content_view->setModel('xepan/marketing/Content')->addCondition('status','Approved')->addCondition('is_template',false);


		/**
			 Common form decleration 
		*/

		$form = $this->add('Form',null,'asso_form');
		$events_field = $form->addField('hidden','events_fields');
		$submit_btn = $form->addButton('Update Schedule')->addClass('btn btn-primary btn-block');

		/**
				getting json encoded event list on form click
		*/

		$js=[
			$this->js()->_selector('#daycalendar')->xepan_subscriptioncalander('to_field',$events_field),
			$form->js()->submit()
		];
		$submit_btn->js('click',$js);

		if($form->isSubmitted()){
			$m['schedule']= $form['events_fields'];
			$m->save();
			
			$day_events = json_decode($form['events_fields'],true);
			$previous_schedule_array = $m->getSchedule();

			foreach ($day_events as $day => $events) {

				foreach($events['events'] as $event_id => $event_value_array){
	
				$model_schedule = $this->add('xepan\marketing\Model_Schedule')
					->addCondition('campaign_id',$_GET['campaign_id'])
					->addCondition('day', $events['duration'])
					->addCondition('document_id',$event_id)
					->tryLoadAny();

					if($model_schedule->loaded()){
						$key = array_search($model_schedule->id, $previous_schedule_array);
						if (false !== $key) {
							unset($previous_schedule_array[$key]);
						}
					}

					$model_schedule->saveAndUnload();
				}
			}

			if(count($previous_schedule_array))
				$this->add('xepan\marketing\Model_Schedule')
					->addCondition('id',$previous_schedule_array)->deleteAll();
			
		 	$form->js(null,$this->js()->univ()->successMessage('Schedule Updated'))->reload()->execute();
		}
	}

	function defaultTemplate(){
		return['page/subscriberschedule'];
	}

	function render(){

		$campaign_id=$this->app->stickyGET('campaign_id');
		$m=$this->add('xepan/marketing/Model_Campaign')->load($campaign_id);

		$event = array();
		$event = json_decode($m['schedule']?:"[]",true);

		$this->js(true)->_load('subscriptioncalendar');
			
		$this->js(true)->_selector('#daycalendar')->xepan_subscriptioncalander(['days'=>$event]);
		parent::render();
	}
}