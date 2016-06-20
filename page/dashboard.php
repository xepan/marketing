<?php

namespace xepan\marketing;

class page_dashboard extends \xepan\base\Page{
	public $title = "Marketing Dashboard";
	function init(){
		parent::init();

		// HEADER FORM
		$form = $this->add('Form',null,'form');
		$form->setLayout('view\dashboard_form');
		$form->addField('line','search','')->setAttr(['placeholder'=>'NLP Search']);
		$cat_d = $form->addField('dropdown','category','');
		$cat_d->setEmptyText('All');
		$cat_d->setModel('xepan\marketing\Model_MarketingCategory');
		$form->addField('line','daterange','')->setAttr(['placeholder'=>'Date range form field']);

		// HEADER FORM SUBMISSION
		if($form->isSubmitted()){
			throw new \Exception("SHOW RESULTS IN FRAME URL WITH GRAPHS");
		}

		// GRAPH AND CHART VIEWS
		$bar_chart = $this->add('xepan\marketing\View_BarChart',null,'bar_chart');
		$graph_stats = $this->add('xepan\marketing\View_GraphStats',null,'graph_stats');
		
		// SOCIAL ACTIVITY LISTER
		$social_lister = $this->add('xepan\marketing\View_SocialLister');
		$social_lister->setModel('xepan\marketing\SocialPosters_Base_SocialConfig');
	}

	function defaultTemplate(){
		return['page/dashboard'];
	}

}