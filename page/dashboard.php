<?php

namespace xepan\marketing;

class page_dashboard extends \xepan\base\Page{
	public $title = "Marketing Dashboard";
	function init(){
		parent::init();

		// HEADER FORM
		$form = $this->add('Form',null,'form_layout');
		$form->setLayout(['page\dashboard','form_layout']);
		$field_from_date = $form->addField('DatePicker','from_date')->validate('required');
		$field_to_date = $form->addField('DatePicker','to_date')->validate('required');
		// $form->addField('DateRangePicker','range')->validate('required');
		$field_group = $form->addField('dropdown','group')->setValueList(['Hours'=>'Hours','Date'=>'Date','Week'=>'Week','Month'=>'Month','Year'=>'Year'])->set('Week');
		$form->addSubmit("Filter")->addClass('btn btn-primary');
		if($form->isSubmitted()){
			if(!$form['from_date'])
				$form->error('from_date','must not be empty');
			
			if(!$form['to_date'])
				$form->error('to_date','must not be empty');
			
			$form->app->redirect($this->app->url(null,['from_date'=>$form['from_date'],'to_date'=>$form['to_date'],'group'=>$form['group']]));
		}

		//GRAPH 1
		// LEAD VS SCORE INCREMENT GRAPH

		$custom_date = strtotime( date('Y-m-d', strtotime($this->app->today)) ); 
		$week_start = date('Y-m-d', strtotime('this week last monday', $custom_date));
		$week_end = date('Y-m-d', strtotime('this week next sunday', $custom_date));

		$from_date = $week_start;
		
		if($this->app->stickyGET('from_date')){
			$from_date = $_GET['from_date'];
			$field_from_date->set($from_date);
		}

		$to_date = $this->app->today;
		if($_GET['to_date']){
			$to_date = $_GET['to_date'];
			$field_to_date->set($to_date);
		}

		$group_by = "Week"; //'Date','Week','Month','Year','Hours'
		if($_GET['group']){
			$group_by = $_GET['group'];
			$field_group->set($group_by);
		}

		$lead_score_data=[];
		// Calculating Lead Count
		$lead = $this->add('xepan\marketing\Model_Lead');
		$lead->addCondition('created_at',">=",$from_date);
		$lead->addCondition('created_at',"<=",$to_date);
		$lead->addExpression('Date','DATE(created_at)');
		// $lead->addExpression('Month','MONTH(created_at)');
		$lead->addExpression('Month','DATE_FORMAT(created_at,"%Y %M")');
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
		$point_system->addExpression('Month','DATE_FORMAT(created_at,"%Y %M")');
		// $point_system->addExpression('Month','MONTH(created_at)');
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

		$lead_vs_score = $this->add('xepan\base\View_Chart',null,'lead_vs_score');
		$lead_vs_score->setChartType("Line");
		$lead_vs_score->setLibrary("Morris");
		$lead_vs_score->setXAxis($group_by);
		$lead_vs_score->setYAxis(['Lead Count','Score Count']);
		$lead_vs_score->setData(array_values($lead_score_data));
		$lead_vs_score->setOption('behaveLikeLine',true);
		$lead_vs_score->setLabels(['Lead Count', 'Score Count']);
		$lead_vs_score->setXLabelAngle(35);
		// GRAPH AND CHART VIEWS
		// HOT LEAD VIEW
		$lead = $this->add('xepan\marketing\Model_Lead');
		
		$lead->addExpression('last_landing_response_date_from_lead')->set(function($m,$q){
			$landing_response = $m->add('xepan\marketing\Model_LandingResponse')
									->addCondition('contact_id',$m->getElement('id'))
									->setLimit(1)
									->setOrder('date','desc');
			return $q->expr("[0]",[$landing_response->fieldQuery('date')]);
		});

		$lead->addExpression('last_communication_date_from_lead')->set(function($m,$q){
			$communication = $m->add('xepan\communication\Model_Communication')->addCondition('from_id',$m->getElement('id'))->addCondition('direction','In')->setLimit(1)->setOrder('created_at','desc');
			return $q->expr("[0]",[$communication->fieldQuery('created_at')]);
		});

		$lead->addExpression('last_communication_date_from_company')->set(function($m,$q){
			$communication = $m->add('xepan\communication\Model_Communication')->addCondition('to_id',$m->getElement('id'))->addCondition('direction','Out')->setLimit(1)->setOrder('created_at','desc');
			return $q->expr("[0]",[$communication->fieldQuery('created_at')]);
		});

		// current date - max from last_landing_from_lead, last_communication_form_lead or last_communication_form_employee
		$lead->addExpression('days_ago')->set(function($m,$q){
			return $q->expr("DATEDIFF([0], IFNULL(GREATEST([1],COALESCE([2],0),COALESCE([3],0)),[0]))",
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

		$lead->addCondition('score','>',0);
		$lead->setOrder('last_communication_date_from_company','desc');
		$lead->setOrder('last_communication_date_from_lead','desc');
		$lead->setOrder('last_landing_response_date_from_lead','desc');
		$lead->setOrder('score','desc');
		$lead->setOrder('priority','desc');
		// $lead->setLimit(10);
		$this->add('Grid',null,'hot_lead')->setModel($lead,['name','score','days_ago','priority','last_landing_response_date_from_lead','last_communication_date_from_lead','last_communication_date_from_company']);

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
		$lead_score_grid = $this->add('Grid',null,'ratio_filter',null);	
		$lead_score_grid->setModel($lead,['name','count','last_communication_date_from_company'])->setLimit(5);
	}

	function defaultTemplate(){
		return['page/dashboard'];
	}

}