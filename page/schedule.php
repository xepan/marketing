<?php
namespace xepan\marketing;

class page_schedule extends \Page{
	
	public $title="Schedule";
	
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
		$form->addSubmit('Update');
		
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
		return['page/schedule'];
	}

	function render(){

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