<?php
namespace xepan\marketing;

class page_schedule extends \xepan\base\Page{
	
	public $title="Schedule";
	public $breadcrumb=['Home'=>'index','Campaign'=>'xepan_marketing_campaign','Schedule'=>'#'];
	function init(){
		parent::init();	
		$campaign_id=$this->app->stickyGET('campaign_id');
		$m=$this->add('xepan/marketing/Model_Campaign')->load($campaign_id);
		
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
			$submit_btn->js()->univ()->getDateEvents($events_field),
			$form->js()->submit()
		];
		$submit_btn->js('click',$js);
		


		if($form->isSubmitted()){
			$m['schedule']= $form['events_fields'];
			$m->save();

 			$events = json_decode($form['events_fields'],true);

			//get All Previous added schedule
			//[28,29]
			$previous_schedule_array = $m->getSchedule();
			// unset one by one according to events

			foreach ($events as $event) {
				if(!$event['document_id'])
					continue;
				
				$model_schedule = $this->add('xepan\marketing\Model_Schedule')
					->addCondition('campaign_id',$_GET['campaign_id'])
					->addCondition('client_event_id',$event['client_event_id'])
					->tryLoadAny();
				
				if($model_schedule->loaded()){
					
					$key = array_search($model_schedule->id, $previous_schedule_array);
					if (false !== $key) {
						unset($previous_schedule_array[$key]);
					}
					
				}
				// $save_del = array();
				$model_schedule['campaign_id'] = $m->id;
				$model_schedule['date'] = $event['start'];
				$model_schedule['document_id'] = $event['document_id'];
				$model_schedule->saveAndUnload();
			}

			//finally delete all Remaining schedule according to previous_schedule array
			if(count($previous_schedule_array))
				$this->add('xepan\marketing\Model_Schedule')
					->addCondition('id',$previous_schedule_array)->deleteAll();

		 	$form->js(null,$this->js()->univ()->successMessage('Schedule Updated'))->reload()->execute();
		}

	}

	function defaultTemplate(){
		return['page/schedule'];
	}

	function render(){
		$campaign_id=$this->app->stickyGET('campaign_id');
		$m=$this->add('xepan/marketing/Model_Campaign')->load($campaign_id);

		$event = array();
		$event = json_decode($m['schedule'],true);

		$this->js(true)->_css('libs/fullcalendar')->_css('compiled/calendar');

		$this->js(true)->_load('moment.min')->_load('fullcalendar.min')->_load('xepan-scheduler');
		$this->js(true)->_selector('#calendar')->univ()->schedularDate($event);
		parent::render();

	}
}