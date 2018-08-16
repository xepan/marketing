<?php

namespace xepan\marketing;

class page_report_employeeleadreport extends \xepan\base\Page{

	public $title = "Employee Lead Report`s";

	function init(){
		parent::init();

		$emp_id = $this->app->stickyGET('employee_id');
		$from_date = $this->app->stickyGET('from_date')?:$this->app->today;
		$to_date = $this->app->stickyGET('to_date')?:$this->app->today;
		$department = $this->app->stickyGET('department');

		$form = $this->add('Form');
		$form->add('xepan\base\Controller_FLC')
			->makePanelsCoppalsible(true)
			->layout([
				'date_range'=>'Filter~c1~3',
				'employee'=>'c2~3',
				'department'=>'c3~3',
				'FormButtons~&nbsp;'=>'c4~3'
			]);

		$date = $form->addField('DateRangePicker','date_range');
		$set_date = $from_date." to ".$to_date;
		$date->set($set_date);

		$emp_field = $form->addField('xepan\base\Basic','employee');
		$emp_field->setModel('xepan\hr\Model_Employee')->addCondition('status','Active');

		$dept_field = $form->addField('xepan\base\DropDown','department');
		$dept_field->setModel('xepan\hr\Model_Department');
		$dept_field->setEmptyText('All');

		$form->addSubmit('Get Details')->addClass('btn btn-primary');

		$emp_model = $this->add('xepan\marketing\Model_EmployeeLead',['from_date'=>$from_date,'to_date'=>$to_date]);
		if($emp_id){
			$emp_model->addCondition('id',$emp_id);
		}
		if($from_date){
			$emp_model->from_date = $from_date;
		}
		if($to_date){
			$emp_model->to_date = $to_date;
		}
		if($department){
			$emp_model->addCondition('department_id',$department);
		}

		$grid = $this->add('xepan\hr\Grid',null,null,['view/report/employee-lead-report-gridview']);
		$grid->setModel($emp_model);
		$grid->add('misc/Export',['export_fields'=>['name','total_lead_created','total_lead_assign_to','total_followup','open_opportunity','qualified_opportunity','needs_analysis_opportunity','quoted_opportunity','negotiated_opportunity','win_opportunity','loss_opportunity']]);
		$grid->addPaginator(50);

		if($form->isSubmitted()){
			$grid->js()->reload(
							[
								'employee_id'=>$form['employee'],
								'from_date'=>$date->getStartDate()?:0,
								'to_date'=>$date->getEndDate()?:0,
								'department'=>$form['department']?:0,
							]
				)->execute();
		}

		$created_lead_vp = $this->add('VirtualPage')->set(function($page){
			// $page->add('View_Error')->set($_GET['from_date']);
			$created_by_id = $this->app->stickyGET('created_by_id');
			$lead = $page->add('xepan\marketing\Model_Lead',['table_alias'=>'employee_created_lead']);
			$lead->addCondition('created_by_id',$created_by_id)
						->addCondition('created_at','>=',$_GET['from_date'])
						->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']));
			$grid = $page->add('xepan\hr\Grid');
			$grid->setModel($lead,['name','created_at','assign_to','emails_str','contacts_str','address','website','last_communication_date_from_lead']);
			$grid->addPaginator(50);
		});

		$grid->addMethod('format_total_lead_created',function($g,$f)use($created_lead_vp){
				// VP defined at top of init function
			$g->current_row_html[$f]= '<a href="javascript:void(0)" onclick="'.$g->js()->univ()->frameURL($g->model['name'].' Created Lead',$g->api->url($created_lead_vp->getURL(),array('created_by_id'=>$g->model->id,'from_date'=>$g->model->from_date,'to_date'=>$g->model->to_date))).'">'.$g->current_row[$f].'</a>';
		});

		$grid->addFormatter('total_lead_created','total_lead_created');

		$assign_lead_vp = $this->add('VirtualPage')->set(function($page){
			// $page->add('View_Error')->set($_GET['from_date']);
			$assign_to_id = $this->app->stickyGET('assign_to_id');
			$lead = $page->add('xepan\marketing\Model_Lead',['table_alias'=>'employee_created_lead']);
			$lead->addCondition('assign_to_id',$assign_to_id)
						->addCondition('created_at','>=',$_GET['from_date'])
						->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']));
			$grid = $page->add('xepan\hr\Grid');
			$grid->setModel($lead,['name','created_at','assign_to','emails_str','contacts_str','address','website','last_communication_date_from_lead']);
			$grid->addPaginator(50);
		});

		$grid->addMethod('format_total_lead_assign_to',function($g,$f)use($assign_lead_vp){
				// VP defined at top of init function
			$g->current_row_html[$f]= '<a href="javascript:void(0)" onclick="'.$g->js()->univ()->frameURL('Lead Assigned To '. $g->model['name'] ,$g->api->url($assign_lead_vp->getURL(),array('assign_to_id'=>$g->model->id,'from_date'=>$g->model->from_date,'to_date'=>$g->model->to_date))).'">'.$g->current_row[$f].'</a>';
		});

		$grid->addFormatter('total_lead_assign_to','total_lead_assign_to');

		$total_followup_vp = $this->add('VirtualPage')->set(function($page){
			$employee_id = $this->app->stickyGET('employee_id');
			$my_followups_model = $this->add('xepan\projects\Model_Task');
		    $my_followups_model->addCondition('assign_to_id',$employee_id)
		    						->addCondition('created_by_id',$employee_id)
		    						->addCondition('created_at','>=',$_GET['from_date'])
									->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']))
		    						->addCondition('type','Followup')
		    						;
			$grid = $page->add('xepan\hr\Grid');
			$grid->setModel($my_followups_model,['task_name','assign_to','related_person','starting_date','deadline','estimate_time','description','status','priority','rejected_at','received_at','submitted_at','reopened_at','completed_at','comment_count']);
			$grid->addPaginator(50);
		});

		$grid->addMethod('format_total_followup',function($g,$f)use($total_followup_vp){
				// VP defined at top of init function
			$g->current_row_html[$f]= '<a href="javascript:void(0)" onclick="'.$g->js()->univ()->frameURL('Total Follow Up ' ,$g->api->url($total_followup_vp->getURL(),array('employee_id'=>$g->model->id,'from_date'=>$g->model->from_date,'to_date'=>$g->model->to_date))).'">'.$g->current_row[$f].'</a>';
		});

		$grid->addFormatter('total_followup','total_followup');

		$open_opportunity_vp = $this->add('VirtualPage')->set(function($page){
			// $page->add('View_Error')->set($_GET['from_date']);
			$employee_id = $this->app->stickyGET('employee_id');
			$opportunity = $this->add('xepan\marketing\Model_Opportunity');
		    $opportunity->addCondition('assign_to_id',$employee_id)
						->addCondition([['created_by_id',$employee_id],['assign_to_id',null]])
						->addCondition('status','Open')
						->addCondition('created_at','>=',$_GET['from_date'])
						->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']))
		    			;

			$grid = $page->add('xepan\hr\Grid');
			$grid->setModel($opportunity,['lead','assign_to','title','description','fund','discount_percentage','closing_date','narration','previous_status','probability_percentage']);
			$grid->addPaginator(50);
		});

		$grid->addMethod('format_open_opportunity',function($g,$f)use($open_opportunity_vp){
				// VP defined at top of init function
			$g->current_row_html[$f]= '<a href="javascript:void(0)" onclick="'.$g->js()->univ()->frameURL('Open Opportunity ',$g->api->url($open_opportunity_vp->getURL(),array('employee_id'=>$g->model->id,'from_date'=>$g->model->from_date,'to_date'=>$g->model->to_date))).'">'.$g->current_row[$f].'</a>';
		});

		$grid->addFormatter('open_opportunity','open_opportunity');

		$qualified_opportunity_vp = $this->add('VirtualPage')->set(function($page){
			// $page->add('View_Error')->set($_GET['from_date']);
			$employee_id = $this->app->stickyGET('employee_id');
			$opportunity = $this->add('xepan\marketing\Model_Opportunity');
		    $opportunity->addCondition('assign_to_id',$employee_id)
						->addCondition([['created_by_id',$employee_id],['assign_to_id',null]])
						->addCondition('status','Qualified')
						->addCondition('created_at','>=',$_GET['from_date'])
						->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']))
		    			;

			$grid = $page->add('xepan\hr\Grid');
			$grid->setModel($opportunity,['lead','assign_to','title','description','fund','discount_percentage','closing_date','narration','previous_status','probability_percentage']);
			$grid->addPaginator(50);
		});

		$grid->addMethod('format_qualified_opportunity',function($g,$f)use($qualified_opportunity_vp){
				// VP defined at top of init function
			$g->current_row_html[$f]= '<a href="javascript:void(0)" onclick="'.$g->js()->univ()->frameURL('Qualified Opportunity ',$g->api->url($qualified_opportunity_vp->getURL(),array('employee_id'=>$g->model->id,'from_date'=>$g->model->from_date,'to_date'=>$g->model->to_date))).'">'.$g->current_row[$f].'</a>';
		});

		$grid->addFormatter('qualified_opportunity','qualified_opportunity');

		$needs_analysis_opportunity_vp = $this->add('VirtualPage')->set(function($page){
			// $page->add('View_Error')->set($_GET['from_date']);
			$employee_id = $this->app->stickyGET('employee_id');
			$opportunity = $this->add('xepan\marketing\Model_Opportunity');
		    $opportunity->addCondition('assign_to_id',$employee_id)
						->addCondition([['created_by_id',$employee_id],['assign_to_id',null]])
						->addCondition('status','NeedsAnalysis')
						->addCondition('created_at','>=',$_GET['from_date'])
						->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']))
		    			;

			$grid = $page->add('xepan\hr\Grid');
			$grid->setModel($opportunity,['lead','assign_to','title','description','fund','discount_percentage','closing_date','narration','previous_status','probability_percentage']);
			$grid->addPaginator(50);
		});

		$grid->addMethod('format_needs_analysis_opportunity',function($g,$f)use($needs_analysis_opportunity_vp){
				// VP defined at top of init function
			$g->current_row_html[$f]= '<a href="javascript:void(0)" onclick="'.$g->js()->univ()->frameURL('NeedsAnalysis Opportunity ',$g->api->url($needs_analysis_opportunity_vp->getURL(),array('employee_id'=>$g->model->id,'from_date'=>$g->model->from_date,'to_date'=>$g->model->to_date))).'">'.$g->current_row[$f].'</a>';
		});

		$grid->addFormatter('needs_analysis_opportunity','needs_analysis_opportunity');

		$quoted_opportunity_vp = $this->add('VirtualPage')->set(function($page){
			// $page->add('View_Error')->set($_GET['from_date']);
			$employee_id = $this->app->stickyGET('employee_id');
			$opportunity = $this->add('xepan\marketing\Model_Opportunity');
		    $opportunity->addCondition('assign_to_id',$employee_id)
						->addCondition([['created_by_id',$employee_id],['assign_to_id',null]])
						->addCondition('status','Quoted')
						->addCondition('created_at','>=',$_GET['from_date'])
						->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']))
		    			;

			$grid = $page->add('xepan\hr\Grid');
			$grid->setModel($opportunity,['lead','assign_to','title','description','fund','discount_percentage','closing_date','narration','previous_status','probability_percentage']);
			$grid->addPaginator(50);
		});

		$grid->addMethod('format_quoted_opportunity',function($g,$f)use($quoted_opportunity_vp){
				// VP defined at top of init function
			$g->current_row_html[$f]= '<a href="javascript:void(0)" onclick="'.$g->js()->univ()->frameURL('Quoted Opportunity ',$g->api->url($quoted_opportunity_vp->getURL(),array('employee_id'=>$g->model->id,'from_date'=>$g->model->from_date,'to_date'=>$g->model->to_date))).'">'.$g->current_row[$f].'</a>';
		});

		$grid->addFormatter('quoted_opportunity','quoted_opportunity');

		$negotiated_opportunity_vp = $this->add('VirtualPage')->set(function($page){
			// $page->add('View_Error')->set($_GET['from_date']);
			$employee_id = $this->app->stickyGET('employee_id');
			$opportunity = $this->add('xepan\marketing\Model_Opportunity');
		    $opportunity->addCondition('assign_to_id',$employee_id)
						->addCondition([['created_by_id',$employee_id],['assign_to_id',null]])
						->addCondition('status','Negotiated')
						->addCondition('created_at','>=',$_GET['from_date'])
						->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']))
		    			;

			$grid = $page->add('xepan\hr\Grid');
			$grid->setModel($opportunity,['lead','assign_to','title','description','fund','discount_percentage','closing_date','narration','previous_status','probability_percentage']);
			$grid->addPaginator(50);
		});

		$grid->addMethod('format_negotiated_opportunity',function($g,$f)use($negotiated_opportunity_vp){
				// VP defined at top of init function
			$g->current_row_html[$f]= '<a href="javascript:void(0)" onclick="'.$g->js()->univ()->frameURL('Negotiated Opportunity ',$g->api->url($negotiated_opportunity_vp->getURL(),array('employee_id'=>$g->model->id,'from_date'=>$g->model->from_date,'to_date'=>$g->model->to_date))).'">'.$g->current_row[$f].'</a>';
		});

		$grid->addFormatter('negotiated_opportunity','negotiated_opportunity');


		$win_opportunity_vp = $this->add('VirtualPage')->set(function($page){
			// $page->add('View_Error')->set($_GET['from_date']);
			$employee_id = $this->app->stickyGET('employee_id');
			$opportunity = $this->add('xepan\marketing\Model_Opportunity');
		    $opportunity->addCondition('assign_to_id',$employee_id)
						->addCondition([['created_by_id',$employee_id],['assign_to_id',null]])
						->addCondition('status','Won')
						->addCondition('created_at','>=',$_GET['from_date'])
						->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']))
		    			;

			$grid = $page->add('xepan\hr\Grid');
			$grid->setModel($opportunity,['lead','assign_to','title','description','fund','discount_percentage','closing_date','narration','previous_status','probability_percentage']);
			$grid->addPaginator(50);
		});

		$grid->addMethod('format_win_opportunity',function($g,$f)use($win_opportunity_vp){
				// VP defined at top of init function
			$g->current_row_html[$f]= '<a href="javascript:void(0)" onclick="'.$g->js()->univ()->frameURL('Won Opportunity ',$g->api->url($win_opportunity_vp->getURL(),array('employee_id'=>$g->model->id,'from_date'=>$g->model->from_date,'to_date'=>$g->model->to_date))).'">'.$g->current_row[$f].'</a>';
		});

		$grid->addFormatter('win_opportunity','win_opportunity');

		$loss_opportunity_vp = $this->add('VirtualPage')->set(function($page){
			// $page->add('View_Error')->set($_GET['from_date']);
			$employee_id = $this->app->stickyGET('employee_id');
			$opportunity = $this->add('xepan\marketing\Model_Opportunity');
		    $opportunity->addCondition('assign_to_id',$employee_id)
						->addCondition([['created_by_id',$employee_id],['assign_to_id',null]])
						->addCondition('status','Lost')
						->addCondition('created_at','>=',$_GET['from_date'])
						->addCondition('created_at','<',$this->api->nextDate($_GET['to_date']))
		    			;

			$grid = $page->add('xepan\hr\Grid');
			$grid->setModel($opportunity,['lead','assign_to','title','description','fund','discount_percentage','closing_date','narration','previous_status','probability_percentage']);
			$grid->addPaginator(50);
		});

		$grid->addMethod('format_loss_opportunity',function($g,$f)use($loss_opportunity_vp){
				// VP defined at top of init function
			$g->current_row_html[$f]= '<a href="javascript:void(0)" onclick="'.$g->js()->univ()->frameURL('Lost Opportunity',$g->api->url($loss_opportunity_vp->getURL(),array('employee_id'=>$g->model->id,'from_date'=>$g->model->from_date,'to_date'=>$g->model->to_date))).'">'.$g->current_row[$f].'</a>';
		});

		$grid->addFormatter('loss_opportunity','loss_opportunity');
	}
}