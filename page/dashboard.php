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
		$group_by = "Month"; //'Date','Week','Month','Year','Hours'

		$lead_score_data=[];

		// Calculating Lead Count
		$lead = $this->add('xepan\marketing\Model_Lead');
		$lead->addCondition('created_at',">=",$from_date);
		$lead->addCondition('created_at',"<=",$to_date);
		$lead->addExpression('Date','DATE(created_at)');
		$lead->addExpression('Month','MONTH(created_at)');
		$lead->addExpression('Year','YEAR(created_at)');
		$lead->addExpression('Week','WEEK(created_at)');
		$lead->addExpression('Hours','HOUR(created_at)');
		$lead->_dsql()->group($lead->dsql()->expr('[0]',[$lead->getElement($group_by)]));

		$lead->_dsql()->del('fields')->field('count(*)leads_count')->field($lead->dsql()->expr('[0]'.$group_by,[$lead->getElement($group_by)]));
		foreach ( $lead->_dsql() as $ld) {
			$lead_score_data[$ld[$group_by]] = [$group_by=>$ld[$group_by],'Lead Count'=>$ld['leads_count'], 'Score Count'=> 0];
		}

		// Calculating Score Count
		$point_system = $this->add('xepan\base\Model_PointSystem');
		$point_system->addCondition('created_at',">=",$from_date);
		$point_system->addCondition('created_at',"<=",$to_date);
		$point_system->addExpression('Date','DATE(created_at)');
		$point_system->addExpression('Month','MONTH(created_at)');
		$point_system->addExpression('Year','YEAR(created_at)');
		$point_system->addExpression('Week','WEEK(created_at)');
		$point_system->addExpression('Hours','HOUR(created_at)');
		$point_system->_dsql()->group($point_system->dsql()->expr('[0]',[$point_system->getElement($group_by)]));
		$point_system->_dsql()->del('fields')->field($point_system->dsql()->expr('IFNULL(sum(score),0)score_count'))->field($point_system->dsql()->expr('[0]'.$group_by,[$point_system->getElement($group_by)]));
		
		foreach ($point_system->_dsql() as $ld) {
			// echo $ld[$group_by]."<br/>";
			if(!isset($lead_score_data[$ld[$group_by]])){
				$lead_score_data[$ld[$group_by]] = [$group_by=>$ld[$group_by],'Lead Count'=> 0];
			}
			$lead_score_data[$ld[$group_by]]['Score Count'] = $ld['score_count'];
			// if(isset($lead_score_data[$ld[$group_by]]))
			// 	$lead_score_data[$ld[$group_by]] = 	
		}
		
		// echo "<pre>";
		// var_dump($lead_score_data);
		// exit;
		// $lead_score_data = [
		// 	[$group_by=> "21", "Score"=> 20,'Lead'=>78],
		// 	[$group_by=> "201", "Score"=> 33, "Lead"=> 629],
		// 	[$group_by=> "203", "Score"=> 30, "Lead"=> 67],
		// 	[$group_by=> "204", "Score"=> 40, "Lead"=> 676],
		// 	[$group_by=> "205", "Score"=> 50, "Lead"=> 681],
		// 	[$group_by=> "206", "Score"=> 60, "Lead"=> 620],
		// 	[$group_by=> "207", "Score"=> 10, "Lead"=> 987],
		// 	[$group_by=> "208", "Score"=> 90, "Lead"=> 89]
		// ];

		$lead_vs_score = $this->add('xepan\base\View_Chart');
		$lead_vs_score->setChartType("Line");
		$lead_vs_score->setLibrary("Morris");
		$lead_vs_score->setXAxis($group_by);
		$lead_vs_score->setYAxis(['Lead Count','Score Count']);
		$lead_vs_score->setData(array_values($lead_score_data));
		$lead_vs_score->setOption('behaveLikeLine',true);
		$lead_vs_score->setLabels(['Lead Count', 'Score Count']);

		// GRAPH AND CHART VIEWS
		// $bar_chart = $this->add('xepan\marketing\View_BarChart');
		// $graph_stats = $this->add('xepan\marketing\View_GraphStats',null,'graph_stats');
		
		// HOT LEAD VIEW
		$lead = $this->add('xepan\marketing\Model_Lead');
		
		
		$lead->addExpression('last_landing_response_date_from_lead')->set(function($m,$q){
			$landing_response = $m->add('xepan\marketing\Model_LandingResponse')->addCondition('contact_id',$m->id)->setLimit(1)->setOrder('date','desc')->tryLoadAny();
			return $q->expr("IFNULL([0],1970-01-01)",[$landing_response->fieldQuery('date')]);
		});

		$lead->addExpression('last_communication_date_from_lead')->set(function($m,$q){
			$communication = $m->add('xepan\communication\Model_Communication')->addCondition('from_id',$m->id)->addCondition('direction','In')->setLimit(1)->setOrder('created_at','desc')->tryLoadAny();
			return $q->expr("IFNULL([0],1970-01-01)",[$communication->fieldQuery('created_at')]);
		});

		$lead->addExpression('last_communication_date_from_company')->set(function($m,$q){
			$communication = $m->add('xepan\communication\Model_Communication')->addCondition('to_id',$m->id)->addCondition('direction','Out')->setLimit(1)->setOrder('created_at','desc')->tryLoadAny();
			return $q->expr("IFNULL([0],1970-01-01)",[$communication->fieldQuery('created_at')]);
		});

		// current date - max from last_landing_from_lead, last_communication_form_lead or last_communication_form_employee
		$lead->addExpression('days_ago')->set(function($m,$q){
			return $q->expr("DATEDIFF([0], GREATEST([1],[2],[3]))",
								[
									'"'.$this->app->now.'"',
									$m->getElement('last_landing_response_date_from_lead'),
									$m->getElement('last_communication_date_from_lead'),
									$m->getElement('last_communication_date_from_company')
								]
						);
		});

		// return days ago * score * k .// here k is constant
		$k = 1;
		$lead->addExpression('priority')->set(function($m,$q)use($k){
			return $q->expr('[0] * [1] * [2]',[$m->getElement('days_ago'),$m->getElement('score'),$k]);
		});

		$lead->setOrder('priority','desc');

		$lead->setLimit(10);
		$this->add('Grid')->setModel($lead,['name','days_ago','priority','last_landing_response_date_from_lead','last_communication_date_from_lead','last_communication_date_from_company']);

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