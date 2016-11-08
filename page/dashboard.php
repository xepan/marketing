<?php

namespace xepan\marketing;

class page_dashboard extends \xepan\base\Page{
	public $title = "Marketing Dashboard";
	
	function init(){
		parent::init();
	    
		$this->start_date = $start_date = $_GET['start_date']?:date("Y-m-d", strtotime('-29 days', strtotime($this->app->today))); 		
		$this->end_date = $end_date = $_GET['end_date']?:$this->app->today;
		
		
		// // HEADER FORM
		$form = $this->add('Form',null,'form_layout');
		// $form->setLayout(['page/mktngdashboard','form_layout']);
		$fld = $form->addField('DateRangePicker','period')
                ->setStartDate($start_date)
                ->setEndDate($end_date)
                // ->showTimer(15)
                // ->getBackDatesSet() // or set to false to remove
                // ->getFutureDatesSet() // or skip to not include
                ;

        $this->end_date = $this->app->nextDate($this->end_date);
		$form->addSubmit("Filter")->addClass('btn btn-primary');
		
		if($form->isSubmitted()){
			$form->app->redirect($this->app->url(null,['start_date'=>$fld->getStartDate()?:0,'end_date'=>$fld->getEndDate()?:0]));
		}

		// ============ CHARTS =============

		$model = $this->add('xepan\marketing\Model_Lead');
		$model->addExpression('lead_count')->set('count(*)');
		$model->addExpression('score_sum')->set(function($m,$q){
			return $q->expr('IFNULL([0],0)',[$this->add('xepan\base\Model_PointSystem')->addCondition('contact_id',$q->getField('id'))->sum('score')]);
		});

		$model->addExpression('Date','DATE(created_at)');
		$model->addExpression('Month','DATE_FORMAT(created_at,"%Y %M")');
		$model->addExpression('Year','YEAR(created_at)');
		$model->addExpression('Week','WEEK(created_at)');

		$model->_dsql()->group('Date');
		$model->addCondition('created_at','>',$this->start_date);
		$model->addCondition('created_at','<',$this->end_date);

		// $data=  ["columns"=> [
  //           ['Lead', 30, 40, 50, 100, 150, 250],
  //           ['Score', 10, 30, 70, 90, 150, 200]
  //       ]];
		$this->add('xepan\base\View_Chart',null,'Charts')
	    		->setType('line')
	    		->setModel($model,'Date',['lead_count','score_sum'])
	    		// ->setData($data)
	    		->addClass('col-md-12')
	    		->setTitle('Lead Count Vs Score')
	    		;


		$model = $this->add('xepan\marketing\Model_Opportunity');
		$model->addExpression('fund_sum')->set('sum(fund)');
		$model->addExpression('source_filled')->set($model->dsql()->expr('IFNULL([0],"unknown")',[$model->getElement('source')]));
		$model->addCondition('status','Won');
		$model->_dsql()->group('source_filled');
		$model->addCondition('created_at','>',$this->start_date);
		$model->addCondition('created_at','<',$this->end_date);

		// ROI of channel
	    $this->add('xepan\base\View_Chart',null,'Charts')
	    		->setType('pie')
	    		->setModel($model,'source_filled',['fund_sum'])
	    		->addClass('col-md-4')
	    		->setTitle('Won Business Sources')
	    		;

		
		$model = $this->add('xepan\marketing\Model_Opportunity');
		$model->addExpression('fund_sum')->set('sum(fund)');
		$model->_dsql()->group('status');
		$model->addCondition('created_at','>',$this->start_date);
		$model->addCondition('created_at','<',$this->end_date);
		
		// sale_current_pipeline
		$this->add('xepan\base\View_Chart',null,'Charts')
	     		->setType('pie')
	     		->setLabelToValue(true)
	    		->setModel($model,'status',['fund_sum'])
	    		->addClass('col-md-4')
	    		->setTitle('Opportunities Pipeline')
	    		;
	    

	    $model = $this->add('xepan\marketing\Model_Opportunity');
		$model->addExpression('fund_sum')->set('sum(fund)');
		$model->addExpression('source_filled')->set($model->dsql()->expr('IFNULL([0],"unknown")',[$model->getElement('source')]));
		$model->_dsql()->group('source_filled');
		$model->addCondition('created_at','>',$this->start_date);
		$model->addCondition('created_at','<',$this->end_date);


	    // engagin_by_channel
	     $this->add('xepan\base\View_Chart',null,'Charts')
	     		->setType('pie')
	     		->setLabelToValue(true)
	    		->setModel($model,'source_filled',['fund_sum'])
	    		->setTitle('Opportunities From Sources')
	    		->addClass('col-md-4');
	    		;
	    
	   	// Sales activity by sale emp

	 //    public $status=[
		// 	'Open',
		// 	'Qualified',
		// 	'NeedsAnalysis',
		// 	'Quoted',
		// 	'Negotiated',
		// 	'Won',
		// 	'Lost'
		// ];
	    $model = $this->add('xepan\hr\Model_Employee');
	    $model->hasMany('xepan\marketing\Opportunity','assign_to_id',null,'Oppertunities');
		$model->addExpression('Open')->set($model->refSQL('Oppertunities')->addCondition('status','Open')->addCondition('created_at','>',$this->start_date)->addCondition('created_at','<',$this->end_date)->sum('fund'));
		$model->addExpression('Qualified')->set($model->refSQL('Oppertunities')->addCondition('status','Qualified')->addCondition('created_at','>',$this->start_date)->addCondition('created_at','<',$this->end_date)->sum('fund'));
		$model->addExpression('NeedsAnalysis')->set($model->refSQL('Oppertunities')->addCondition('status','NeedsAnalysis')->addCondition('created_at','>',$this->start_date)->addCondition('created_at','<',$this->end_date)->sum('fund'));
		$model->addExpression('Quoted')->set($model->refSQL('Oppertunities')->addCondition('status','Quoted')->addCondition('created_at','>',$this->start_date)->addCondition('created_at','<',$this->end_date)->sum('fund'));
		$model->addExpression('Negotiated')->set($model->refSQL('Oppertunities')->addCondition('status','Negotiated')->addCondition('created_at','>',$this->start_date)->addCondition('created_at','<',$this->end_date)->sum('fund'));
		$model->addExpression('Won')->set($model->refSQL('Oppertunities')->addCondition('status','Won')->addCondition('created_at','>',$this->start_date)->addCondition('created_at','<',$this->end_date)->sum('fund'));
		$model->addExpression('Lost')->set($model->refSQL('Oppertunities')->addCondition('status','Lost')->addCondition('created_at','>',$this->start_date)->addCondition('created_at','<',$this->end_date)->sum('fund'));
		
		$model->addCondition([['Open','>',0],['Qualified','>',0],['NeedsAnalysis','>',0],['Quoted','>',0],['Negotiated','>',0]]);
		$model->addCondition('status','Active');

     	$this->add('xepan\base\View_Chart',null,'Charts')
     		->setType('bar')
     		->setModel($model,'name',['Open','Qualified','NeedsAnalysis','Quoted','Negotiated'])
     		->setGroup(['Open','Qualified','NeedsAnalysis','Quoted','Negotiated'])
     		->setTitle('Sales Staff Status')
     		->addClass('col-md-8')
     		->rotateAxis()
     		;


     	// Communications by staff 
     	$model = $this->add('xepan\hr\Model_Employee');
	    // $model->hasMany('xepan\communication\Communication','from_id',null,'FromCommunications');
	    // $model->hasMany('xepan\communication\Communication','to_id',null,'ToCommunications');
		
		$model->addExpression('Email')->set(function($m,$q){
			return $this->add('xepan\communication\Model_Communication')
						->addCondition([['from_id',$q->getField('id')],['to_id',$q->getField('id')]])
						->addCondition('communication_type','Email')
						->addCondition('status','<>','Outbox')
						->addCondition('created_at','>',$this->start_date)
						->addCondition('created_at','<',$this->end_date)
						->count();
		});

		$model->addExpression('Newsletter')->set(function($m,$q){
			return $this->add('xepan\communication\Model_Communication')
						->addCondition([['from_id',$q->getField('id')],['to_id',$q->getField('id')]])
						->addCondition('communication_type','Newsletter')
						->addCondition('status','<>','Outbox')
						->addCondition('created_at','>',$this->start_date)
						->addCondition('created_at','<',$this->end_date)
						->count();
		});

		$model->addExpression('Call')->set(function($m,$q){
			return $this->add('xepan\communication\Model_Communication')
						->addCondition([['from_id',$q->getField('id')],['to_id',$q->getField('id')]])
						->addCondition('communication_type','Call')
						->addCondition('created_at','>',$this->start_date)
						->addCondition('created_at','<',$this->end_date)
						->count();
		});

		$model->addExpression('TeleMarketing')->set(function($m,$q){
			return $this->add('xepan\communication\Model_Communication')
						->addCondition([['from_id',$q->getField('id')],['to_id',$q->getField('id')]])
						->addCondition('communication_type','TeleMarketing')
						->addCondition('created_at','>',$this->start_date)
						->addCondition('created_at','<',$this->end_date)
						->count();
		});

		$model->addExpression('Meeting')->set(function($m,$q){
			return $this->add('xepan\communication\Model_Communication')
						->addCondition([['from_id',$q->getField('id')],['to_id',$q->getField('id')]])
						->addCondition('communication_type','Personal')
						->addCondition('created_at','>',$this->start_date)
						->addCondition('created_at','<',$this->end_date)
						->count();
		});

		// $model->addExpression('SMS')->set($model->refSQL('Oppertunities')->addCondition('status','Qualified')->sum('fund'));
		// $model->addExpression('TeleMarketing')->set($model->refSQL('Oppertunities')->addCondition('status','NeedsAnalysis')->sum('fund'));
		// $model->addExpression('Phone')->set($model->refSQL('Oppertunities')->addCondition('status','Quoted')->sum('fund'));
		// $model->addExpression('Meetings')->set($model->refSQL('Oppertunities')->addCondition('status','Negotiated')->sum('fund'));

		$model->addCondition([['Email','>',0],['Call','>',0],['Meeting','>',0],['TeleMarketing','>',0],['Newsletter','>',0]]);
		$model->addCondition('status','Active');

     	$this->add('xepan\base\View_Chart',null,'Charts')
     		->setType('bar')
     		->setModel($model,'name',['Email','Newsletter','Call','Meeting','TeleMarketing'])
     		->setGroup(['Email','Newsletter','Call','Meeting','TeleMarketing'])
     		->setTitle('Sales Staff Communication')
     		->addClass('col-md-8')
     		->rotateAxis()
     		;

	    return;

	    // customer-satisfaction
     	$this->add('xepan\base\View_Chart',null,'customer_satisfaction')
     		->setData(['columns'=> [
						        ['Social Marketing', 100],
					            ['Email', 70],
					            ['SMS', 40],
				        	],
				        'type'=>'donut'
				    	])
     		->mergeOptions(['donut'=> ['title'=> "Lead engegged by channel"]]);

     	//month over month growth
     	$this->add('xepan\base\View_Chart',null,'month_over_month_growth')
     		->setData([
     				"columns"=> [
			            ['data1', 30, 20, 50, 40, 60, 50],
			            ['data2', 200, 130, 90, 240, 130, 220],
			            ['data3', 300, 200, 160, 400, 250, 250],
			            ['data4', 200, 130, 90, 240, 130, 220],
			            ['data5', 130, 120, 150, 140, 160, 150],
			            ['data6', 90, 70, 20, 50, 60, 120],
			        ],
			        "type"=> 'bar',
			        "types"=> [
			            "data3"=> 'spline',
			            "data4"=> 'line',
			            "data6"=> 'area',
			        ],
			        "groups"=> [
			            ['data1','data2']
			        ]
     			]);
		// //GRAPH 1
		// // LEAD VS SCORE INCREMENT GRAPH
		
		// if($this->app->stickyGET('from_date')){
		// 	$from_date = $_GET['from_date'];
		// 	$field_from_date->set($from_date);
		// }

		// $to_date = $this->app->today;
		// if($_GET['to_date']){
		// 	$to_date = $_GET['to_date'];
		// 	$field_to_date->set($to_date);
		// }

		// $group_by = "Date"; //'Date','Week','Month','Year','Hours'
		// if($_GET['group']){
		// 	$group_by = $_GET['group'];
		// 	$field_group->set($group_by);
		// }

		// $lead_score_data=[];
		// // Calculating Lead Count
		// $lead = $this->add('xepan\marketing\Model_Lead');
		// $lead->addCondition('created_at',">=",$from_date);
		// $lead->addCondition('created_at',"<=",$to_date);
		// $lead->addExpression('Date','DATE(created_at)');
		// // $lead->addExpression('Month','MONTH(created_at)');
		// $lead->addExpression('Month','DATE_FORMAT(created_at,"%Y %M")');
		// $lead->addExpression('Year','YEAR(created_at)');
		// $lead->addExpression('Week','WEEK(created_at)');
		// $lead->addExpression('Hours','HOUR(created_at)');
		// $lead->_dsql()->group($lead->dsql()->expr('[0]',[$lead->getElement($group_by)]));

		// $lead->_dsql()->del('fields')->field('count(*)leads_count')->field($lead->dsql()->expr('[0]'.$group_by,[$lead->getElement($group_by)]));
		// foreach ( $lead->_dsql() as $ld) {
		// 	$lead_score_data[$ld[$group_by]] = [$group_by=>$ld[$group_by],'Lead Count'=>$ld['leads_count'], 'Score Count'=> 0];
		// }

		// // Calculating Score Count
		// $point_system = $this->add('xepan\base\Model_PointSystem');
		// $point_system->addCondition('created_at',">=",$from_date);
		// $point_system->addCondition('created_at',"<=",$to_date);
		// $point_system->addExpression('Date','DATE(created_at)');
		// $point_system->addExpression('Month','DATE_FORMAT(created_at,"%Y %M")');
		// // $point_system->addExpression('Month','MONTH(created_at)');
		// $point_system->addExpression('Year','YEAR(created_at)');
		// $point_system->addExpression('Week','WEEK(created_at)');
		// $point_system->addExpression('Hours','HOUR(created_at)');
		// $point_system->_dsql()->group($point_system->dsql()->expr('[0]',[$point_system->getElement($group_by)]));
		// $point_system->_dsql()->del('fields')->field($point_system->dsql()->expr('IFNULL(sum(score),0)score_count'))->field($point_system->dsql()->expr('[0]'.$group_by,[$point_system->getElement($group_by)]));
		
		// foreach ($point_system->_dsql() as $ld) {
		// 	if(!isset($lead_score_data[$ld[$group_by]])){
		// 		$lead_score_data[$ld[$group_by]] = [$group_by=>$ld[$group_by],'Lead Count'=> 0];
		// 	}
		// 	$lead_score_data[$ld[$group_by]]['Score Count'] = $ld['score_count'];
		// }
		
		
		// $lead_vs_score = $this->add('xepan\base\View_Chart',null,'lead_vs_score');
		// $lead_vs_score->setChartType("Line");
		// $lead_vs_score->setLibrary("Morris");
		// $lead_vs_score->setXAxis($group_by);
		// $lead_vs_score->setYAxis(['Lead Count','Score Count']);
		// $lead_vs_score->setData(array_values($lead_score_data));
		// $lead_vs_score->setOption('behaveLikeLine',true);
		// $lead_vs_score->setLabels(['Lead Count', 'Score Count']);
		// $lead_vs_score->setXLabelAngle(35);
		// // GRAPH AND CHART VIEWS
		// // HOT LEAD VIEW
		// $lead = $this->add('xepan\marketing\Model_Lead');
		
		
		// $lead->addExpression('last_landing_response_date_from_lead')->set(function($m,$q){
		// 	$landing_response = $m->add('xepan\marketing\Model_LandingResponse')
		// 							->addCondition('contact_id',$m->getElement('id'))
		// 							->setLimit(1)
		// 							->setOrder('date','desc');
		// 	return $q->expr("[0]",[$landing_response->fieldQuery('date')]);
		// });

		// $lead->addExpression('last_communication_date_from_lead')->set(function($m,$q){
		// 	$communication = $m->add('xepan\communication\Model_Communication')->addCondition('from_id',$m->getElement('id'))->addCondition('direction','In')->setLimit(1)->setOrder('created_at','desc');
		// 	return $q->expr("[0]",[$communication->fieldQuery('created_at')]);
		// });


		// $lead->addExpression('last_communication_date_from_company')->set(function($m,$q){
		// 	$communication = $m->add('xepan\communication\Model_Communication')->addCondition('to_id',$m->getElement('id'))->addCondition('direction','Out')->setLimit(1)->setOrder('created_at','desc');
		// 	return $q->expr("[0]",[$communication->fieldQuery('created_at')]);
		// });

		// // current date - max from last_landing_from_lead, last_communication_form_lead or last_communication_form_employee
		// $lead->addExpression('days_ago')->set(function($m,$q){
		// 	return $q->expr("DATEDIFF([0], IFNULL(GREATEST([1],COALESCE([2],0),COALESCE([3],0)),[0]))",
		// 						[
		// 							'"'.$this->app->now.'"',
		// 							$m->getElement('last_landing_response_date_from_lead'),
		// 							$m->getElement('last_communication_date_from_lead'),
		// 							$m->getElement('last_communication_date_from_company')
		// 						]
		// 				);
		// });

		// // return days ago * score * k .// here k is constant
		// $k = 1;
		// $lead->addExpression('priority')->set(function($m,$q)use($k){
		// 	return $q->expr('[0] * [1] * [2]',[$m->getElement('days_ago'),$m->getElement('score'),$k]);
		// });

		// $lead->addCondition('score','>',0);
		// $lead->setOrder('last_communication_date_from_company','desc');
		// $lead->setOrder('last_communication_date_from_lead','desc');
		// $lead->setOrder('last_landing_response_date_from_lead','desc');
		// $lead->setOrder('score','desc');
		// $lead->setOrder('priority','desc');
		// // $lead->setLimit(10);

		// $hot_lead_grid = $this->add('xepan\hr\Grid',null,'hot_lead',['view\dashboard\hot-lead-grid']);
		
		// $hot_lead_grid->addHook('formatRow',function($g){
		// 	$xdate = $this->add('xepan\base\xDate');
		// 	$reponse_lead_date = $xdate->diff(
		// 						date("Y-m-d H:i:s",strtotime($g->app->now)),
		// 						date('Y-m-d H:i:s',strtotime($g->model['last_landing_response_date_from_lead']?:$g->model['created_at']))
		// 					);

		// 	$reponse_communication_lead_date = $xdate->diff(
		// 						date("Y-m-d H:i:s",strtotime($g->app->now)),
		// 						date('Y-m-d H:i:s',strtotime($g->model['last_communication_date_from_lead']?:$g->model['created_at']))
		// 					);	

		// 	$reponse_communication_company_date = $xdate->diff(
		// 						date("Y-m-d H:i:s",strtotime($g->app->now)),
		// 						date('Y-m-d H:i:s',strtotime($g->model['last_communication_date_from_company']?:$g->model['created_at']))
		// 					);				
		// 	$g->current_row_html['landing_response_date_from_lead'] = $reponse_lead_date;
		// 	$g->current_row_html['communication_date_from_lead'] = $reponse_communication_lead_date;
		// 	$g->current_row_html['communication_date_from_company'] = $reponse_communication_company_date;
		// });
		// $hot_lead_grid->setModel($lead,['name','score','days_ago','priority','landing_response_date_from_lead','communication_date_from_lead','communication_date_from_company']);
		// $lead_score_grid = $this->add('xepan\base\Grid',null,'ratio_filter',['view\leadscore']);	
		// $lead_score_grid->setModel($lead)->setOrder('id','desc');
		// $lead_score_grid->addPaginator(5);
		// $lead_score_grid->template->trySet('heading','Recent Scores');
		
	}

	function defaultTemplate(){
		return['page/mktngdashboard'];
	}
}