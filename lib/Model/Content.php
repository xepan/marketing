<?php

namespace xepan\marketing;  

class Model_Content extends \xepan\hr\Model_Document{

	public $status=[
		'Draft',
		'Submitted',
		'Approved',
		'Rejected'
	];
	public $actions=[
		'Draft'=>['view','edit','delete','submit','test'],
		'Submitted'=>['view','reject','approve','edit','delete','test'],
		'Approved'=>['view','reject','schedule','edit','delete','test','get_url'],
		'Rejected'=>['view','edit','delete','submit','test']
	];

	function page_get_url($p){
		
		$form = $p->add('Form');
		$form->addField('page');
		$form->addField('source')->addClass('xepan-push-large')->validate('required');
		$form->addField('js_redirect_to_url')->addClass('xepan-push-large');
		$form->addSubmit('Get traceable URL')->addClass('btn btn-primary xepan-push-large');
		$view = $p->add('View');
		
		$page='index';
		$source='Social';

		if($_GET['url']) $page=$_GET['url'];
		if($_GET['source']) $source=$_GET['source'];
		if($_GET['js_redirect_to_url']) $source .= '&js_redirect_url='.$_GET['js_redirect_to_url'];

		$view->set($this->app->pm->base_url."?page=$page&xepan_landing_content_id=$this->id&source=$source");

		if($form->isSubmitted()){
			$url = $form['input_your_websites_url'].'/?&xepan_landing_content_id='.$this->id;
			$view->js()->reload(['url'=>$form['page'],'source'=>$form['source'],'js_redirect_to_url'=>$form['js_redirect_to_url']])->execute();
		}
	}

	function init(){
		parent::init();

		$cont_j=$this->join('content.document_id');
		$cont_j->hasone('xepan\marketing\MarketingCategory','marketing_category_id');
		$cont_j->addField('message_255')->type('text');
		$cont_j->addField('message_blog')->type('text')->display(['form'=>'xepan\base\RichText']);
		$cont_j->addField('url');
		$cont_j->addField('title');
		$cont_j->addField('content_name');
		$cont_j->addField('is_template')->type('boolean')->defaultValue(false);
		$cont_j->hasMany('xepan/marketing/Schedule','document_id');


		$this->getElement('status')->defaultValue('Draft');

		$this->is([
			'marketing_category_id|required',
			'title|required'
			]);

		$this->addHook('beforeDelete',[$this,'checkExistingSchedule']);
		$this->addHook('beforeSave',[$this,'updateSearchString']);


	}

	function updateSearchString($m){
		$search_string = ' ';
		$search_string .=" ". $this['title'];
		$search_string .=" ". $this['type'];
		$search_string .=" ". $this['message_255'];
		$search_string .=" ". $this['message_blog'];
		$search_string .=" ". $this['status'];

		$this['search_string'] = $search_string;
	}

	function checkExistingSchedule($m){
		$schedule_count = $m->ref('xepan/marketing/Schedule')->count()->getOne();

		if($schedule_count)
			throw new \Exception('Remove it from schedule first');
			
	}	

	function page_schedule($p){
		if(!$this->loaded())
			throw new \Exception('Model not loaded');		
		
		$tabs = $p->add('Tabs')->addClass('xepan-push-large');
        $calendar = $tabs->addTab('Calendar');
        $subscription = $tabs->addTab('Subscription');

		$view = $p->add('View')->addClass('panel panel-default');
		
		if($campaign_id = $_GET['camp_id']){
			$schedule_m = $this->add('xepan\marketing\Model_Schedule');
			$schedule_m->addCondition('campaign_id',$campaign_id);
			
			$schedule_m->addExpression('title')->set(function($m,$q){
				$content = $this->add('xepan\marketing\Model_Content');	
				$content->addCondition('id',$m->getElement('document_id'));
				$content->setLimit(1);
				return $content->fieldQuery('title');
			});	
			
			$grid = $view->add('xepan\base\Grid');
			$grid->setModel($schedule_m,['title','date','day','posted_on','content_type']);
			$grid->addFormatter('title','ShortText');
		}

		
		$calendarform = $calendar->add('Form');		
		$calendar_campaign_field = $calendarform->addField('Dropdown','campaign');
		$calendar_campaign_field->validate('required');
		$calendar_campaign_field->setEmptyText('Please select a campaign')->setModel('xepan\marketing\Model_Campaign')->addCondition('campaign_type','campaign');
		$calendarform->addField('DatePicker','date')->validate('required');
		$calendarform->addField('TimePicker','time')->addClass('xepan-push-small')->validate('required');
		$calendarform->addSubmit('Schedule')->addClass('btn btn-primary btn-block');

		$calendar_campaign_field->js('change',$view->js()->reload(null,null,[$this->app->url(null,['cut_object'=>$view->name]),'camp_id'=>$calendar_campaign_field->js()->val()]));

		$subscription_form = $subscription->add('Form');		
		$subscription_campaign_field = $subscription_form->addField('Dropdown','campaign');
		$subscription_campaign_field->validate('required');
		$subscription_campaign_field->setEmptyText('Please select a campaign')->setModel('xepan\marketing\Model_Campaign')->addCondition('campaign_type','subscription');
		$subscription_form->addField('day')->addClass('xepan-push-small')->validate('required');
		$subscription_form->addSubmit('Schedule')->addClass('btn btn-primary btn-block');

		$subscription_campaign_field->js('change',$view->js()->reload(null,null,[$this->app->url(null,['cut_object'=>$view->name]),'camp_id'=>$subscription_campaign_field->js()->val()]));

		if($calendarform->isSubmitted()){
			if(!$calendarform['date'])				
				$calendarform->error('date','Date field is mandatory');

			$schedule_time = date("H:i:s", strtotime($calendarform['time']));
			$schedule_date = $calendarform['date'].' '.$schedule_time;
			
			$campaign = $this->add('xepan\marketing\Model_Campaign');
			$schedule = $this->add('xepan\marketing\Model_Schedule');

			$schedule['campaign_id'] = $calendarform['campaign'];
			$schedule['document_id'] = $this->id;
			$schedule['date'] = $schedule_date; 
			$schedule['client_event_id'] = '_fc'.uniqid(); 
			$schedule->save();
			
			$campaign->tryLoadBy('id',$calendarform['campaign']);
			
			$old_schedule = json_decode($campaign['schedule'],true);
			$temp = Array ( 
				'title' => $this['title'], 
				'start' => $schedule_date, 
				'document_id' => $this->id, 
				'client_event_id' => $schedule['client_event_id'] 
			);
			
			$old_schedule[] = $temp;

			$campaign['schedule'] = json_encode($old_schedule);
			$campaign->save();

			return $calendarform->js(null,$calendarform->js()->closest('.dialog')->dialog('close'))->univ()->successMessage('Content Scheduled')->execute();
		}

		if($subscription_form->isSubmitted()){
			if(!$subscription_form['day'])				
				$subscription_form->error('day','Day field is mandatory');
			
			$campaign = $this->add('xepan\marketing\Model_Campaign');
			$schedule = $this->add('xepan\marketing\Model_Schedule');

			$schedule['campaign_id'] = $subscription_form['campaign'];
			$schedule['document_id'] = $this->id;
			$schedule['day'] = $subscription_form['day']; 
			$schedule['client_event_id'] = '_fc'.uniqid(); 
			$schedule->save();
			
			$campaign->tryLoadBy('id',$subscription_form['campaign']);
			
			if(!$campaign->loaded())
				throw new \Exception("Campaign not found");

			$old_schedule = json_decode($campaign['schedule'],true);
			$array = Array
			        (
			            'duration' => $subscription_form['day'],
			            'events' => Array
			                (
			                    $this['id'] => Array
			                        (
			                            'event' => Array
			                                (
			                                    'title' => $this['title'],
			                                    '_nid' => $this['id'],
			                                    'document_id' => $this['id'],
			                                    'start' => null,
			                                    'contenttype' =>'Newsletter', 
			                                )

			                        )

			                )

			        );

			$old_schedule [$subscription_form['day']] = $array;
			
			$campaign['schedule'] = json_encode($old_schedule);
			$campaign->save();

			return $subscription_form->js(null,$subscription_form->js()->closest('.dialog')->dialog('close'))->univ()->successMessage('Content Scheduled')->execute();
		}
	}
} 