<?php

namespace xepan\marketing;

class page_dashboard extends \xepan\base\Page{
	public $title = "Marketing Dashboard";
	function init(){
		parent::init();
		// HEADER FORM
		// $form = $this->add('Form',null,'form');
		// $form->setLayout('view\dashboard_form');
		// $form->addField('line','search','')->setAttr(['placeholder'=>'NLP Search']);
		// $cat_d = $form->addField('dropdown','category','');
		// $cat_d->setEmptyText('All');
		// $cat_d->setModel('xepan\marketing\Model_MarketingCategory');
		// $form->addField('line','daterange','')->setAttr(['placeholder'=>'Date range form field']);

		// // HEADER FORM SUBMISSION
		// if($form->isSubmitted()){
		// 	throw new \Exception("SHOW RESULTS IN FRAME URL WITH GRAPHS");
		// }

		//GRAPH 1
		// LEAD VS SCORE INCREMENT GRAPH
		$from_date = "2001-01-01";
		$to_date = $this->app->today;
		$time_stamp = "Year";

		$lead = $this->add('xepan\marketing\Model_Lead');
		$lead->addCondition('created_at',">=",$from_date);
		$lead->addCondition('created_at',"<=",$to_date);
		$lead->addExpression('');

		$tax_data = [
			["Year"=> "2001", "Score"=> 20,'Lead'=>78],
			["Year"=> "2001", "Score"=> 33, "Lead"=> 629],
			["Year"=> "2003", "Score"=> 30, "Lead"=> 67],
			["Year"=> "2004", "Score"=> 40, "Lead"=> 676],
			["Year"=> "2005", "Score"=> 50, "Lead"=> 681],
			["Year"=> "2006", "Score"=> 60, "Lead"=> 620],
			["Year"=> "2007", "Score"=> 10, "Lead"=> 987],
			["Year"=> "2008", "Score"=> 90, "Lead"=> 89]
		];

		$lead_vs_score = $this->add('xepan\base\View_Chart');
		$lead_vs_score->setChartType("Line");
		$lead_vs_score->setLibrary("Morris");
		$lead_vs_score->setXAxis('Year');
		$lead_vs_score->setYAxis(['Score','Lead']);
		$lead_vs_score->setData($tax_data);
		$lead_vs_score->setOption('behaveLikeLine',true);
		$lead_vs_score->setLabels(['Score', 'Lead']);

		// GRAPH AND CHART VIEWS
		$bar_chart = $this->add('xepan\marketing\View_BarChart');
		// $graph_stats = $this->add('xepan\marketing\View_GraphStats',null,'graph_stats');
		
		// SOCIAL ACTIVITY LISTER
		// $social_lister = $this->add('xepan\marketing\View_SocialLister',null,'social_lister');
		// $social_lister->setModel('xepan\marketing\SocialPosters_Base_SocialConfig');

		// // MAP
		// $map = $this->add('xepan\marketing\View_Map',null,'map');

		// // LEAD RESPONSE
		// $lead_response = $this->add('xepan\marketing\View_LeadResponse',null,'lead_response',['view/leadresponse']);
		
		// // CAMPAIGN REPONSE
		// $campaign_response = $this->add('xepan\hr\Grid',null,'campaign_response',['view/campaignresponse']);
		// $campaign_response->setModel('xepan\marketing\Dashboard')->addCondition('ending_date','<',$this->app->today);
	}

	// function defaultTemplate(){
	// 	return['page/dashboard'];
	// }

}