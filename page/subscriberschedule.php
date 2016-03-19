<?php

namespace xepan\marketing;

class page_subscriberschedule extends \Page{
	public $title = 'Schedule';

	function init(){
		parent::init();

		$campaign_id=$this->app->stickyGET('campaign_id');
		$m=$this->add('xepan/marketing/Model_Campaign')->load($campaign_id);
		
		$content_view = $this->add('xepan/marketing/View_ScheduleContent',null,'MarketingContent');
		$content_view->setModel('xepan/marketing/Content');

		/**
			 Common form decleration 
		*/

		$form = $this->add('Form',null,'asso_form');
		$cat_ass_field = $form->addField('hidden','ass_cat')->set(json_encode($m->getAssociatedCategories()));
		$usr_ass_field = $form->addField('hidden','ass_usr')->set(json_encode($m->getAssociatedUsers()));
		$events_field = $form->addField('Text','events_fields');
		$submit_btn = $form->addButton('Update');

		/**
				getting json encoded event list on form click
		*/

		$js=[
			$this->js()->_selector('#daycalendar')->xepan_subscriptioncalander('to_field',$events_field),
			$form->js()->submit()
		];
		$submit_btn->js('click',$js);


		
		/**
			 campaign and category association.
		*/ 
	
		$category_assoc_grid = $this->add('xepan/base/Grid',null,'Category',['view\schedulecategory']);
		$model_assoc_category = $this->add('xepan\marketing\Model_MarketingCategory');

		$category_assoc_grid->setModel($model_assoc_category);
		$category_assoc_grid->addSelectable($cat_ass_field);

		/**
			 social user and campaign association
		*/

		 $user_assoc_grid = $this->add('xepan/base/Grid',null,'SocialUsers',['view\schedulesocialuser']);
		 $model_assoc_user = $this->add('xepan/marketing/Model_SocialUser');

		 $user_assoc_grid->setModel($model_assoc_user);
		 $user_assoc_grid->addSelectable($usr_ass_field);

		/**
			 Common form submitted 
		*/


		if($form->isSubmitted()){
			$m->removeAssociateCategory();
			$m->removeAssociateUser();

			$m['schedule']= $form['events_fields'];
			$m->save();
			
			$day_events = json_decode($form['events_fields'],true);

			foreach ($day_events as $day => $events) {
	
				$model_schedule = $this->add('xepan\marketing\Model_Schedule');
				
				foreach($events['events'] as $event_id => $event_value_array){
					
					$model_schedule['campaign_id'] = $m->id; 
					$model_schedule['document_id'] = $event_id;
					$model_schedule['day'] = $events['duration'];
					$model_schedule->saveAndUnload();
				}
			}

			$model_asso = $this->add('xepan\marketing\Model_Campaign_Category_Association');
			$model_user_asso = $this->add('xepan\marketing\Model_Campaign_SocialUser_Association');
			
			$selected_categories = array();
			$selected_categories = json_decode($form['ass_cat'],true);
			$selected_user = array();
		 	$selected_user = json_decode($form['ass_usr'],true);

			foreach ($selected_categories as $cat) {
				$model_asso['campaign_id']=$m->id;
				$model_asso['marketing_category_id']=$cat;
				$model_asso->saveAndUnload();
			}

		 	foreach ($selected_user as $usr) {				
		 		$model_user_asso['campaign_id']=$m->id;
		 		$model_user_asso['socialuser_id']=$usr;
		 		$model_user_asso->saveAndUnload();
		 	}
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
		$event = json_decode($m['schedule'],true);

		$this->js(true)->_load('subscriptioncalendar');
		$this->js(true)->_selector('#daycalendar')->xepan_subscriptioncalander(['days'=>$event]);
		parent::render();
	}
}