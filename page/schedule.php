<?php
namespace xepan\marketing;

class page_schedule extends \Page{
	
	public $title="Schedule";
	
	function init(){
		parent::init();	

		$m=$this->add('xepan/marketing/Model_Campaign');

		$content_view = $this->add('xepan/marketing/View_ScheduleContent',null,'MarketingContent');
		$content_view->setModel('xepan/marketing/Content');

		/**
			campaign and category association.
		*/ 

		$category_assoc_grid = $this->add('xepan/base/Grid',null,'Category',['view\schedulecategory']);
		$model_assoc_category =$this->add('xepan/marketing/Model_Campaign_Category_Association')->tryLoadAny();

		$form = $this->add('Form',null,'asso_form');
		$ass_field = $form->addField('hidden','ass_cat')->set(json_encode($m->getAssociatedCategories()));
		$form->addSubmit('Save');

		$category_assoc_grid->setModel($model_assoc_category);
		$category_assoc_grid->addSelectable($form);

		// if($form->isSubmitted()){
		// 		$category->ref('xepan\commerce\CampaingCategory')->deleteAll();

		// 		$selected_categories = array();
		// 		$selected_categories = json_decode($form['ass_cat'],true);
		// 		foreach ($selected_categories as $cat_id) {
		// 			$model_asso = $this->add('xepan\commerce\Model_CategoryItemAssociation');
		// 			$model_asso->addCondition('category_id',$cat_id);
		// 			$model_asso->addCondition('item_id',$item->id);
		// 			$model_asso->tryLoadAny();
		// 			$model_asso->saveAndUnload();
		// 		}
		// 		$form->js(null,$this->js()->univ()->successMessage('Category Associated'))->reload()->execute();
		// 	}
		

	}

	function defaultTemplate(){
		return['page/schedule'];
	}

	function render(){

		// $this->app->jquery->addStylesheet('libs/fullcalendar');
		// $this->app->jquery->addStylesheet('libs/fullcalendar.print');
		// $this->app->jquery->addStylesheet('compiled/calendar');

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