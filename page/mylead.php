<?php

namespace xepan\marketing;

class page_mylead extends \xepan\base\Page{
	public $title = "MY Sales Page"; 
	
	function init(){
		parent::init();

		if(!($employee_id = $this->app->stickyGET('employee_id'))){
			$employee_id= $this->app->employee->id;
		}

		if($this->app->auth->model->isSuperUser()){

			$employee_name = $this->app->employee['name'];
			if($employee_id !== $this->app->employee->id){
				$employee_name = $this->add('xepan\hr\Model_Employee')->load($employee_id)->get('name');
			}

			$f=$this->add('Form');
			$f->add('xepan\base\Controller_FLC')
			->showLables(true)
			->makePanelsCoppalsible(true)
			->layout([
					'employee'=>'Change Employee (' .($employee_name).')~c1~10~closed',
					'FormButtons~'=>'c2~2'
				]);
			$emp_field = $f->addField('xepan\hr\Employee','employee')->set($employee_id);
			$emp_field->js('change',$f->js()->submit());
			$emp_field->addClass('multiselect-full-width');

			$f->addSubmit('Update')->addClass('btn btn-primary');

			if($f->isSubmitted()){
				$this->js()->reload(['employee_id'=>$f['employee']])->execute();
			}

		}

		$tabs = $this->add('Tabs');

		$schedule_cal_tab = $tabs->addTab('My Schedule Calendar');
		$follow_tab = $tabs->addTabURL($this->app->url('xepan_projects_myfollowups',['filter_for_employee_id'=>$employee_id]),'My Followups');
		$oppo_tab = $tabs->addTab('My Opportunities');
		$leads_tab = $tabs->addTab('My Leads');
		$tasks_tab = $tabs->addTab('My Tasks');


		// My Followups


		// $schedule_cal_tab

		$m = $schedule_cal_tab->add('xepan\projects\Model_Task');
		$m->setOrder('starting_date','desc');
		$m->addCondition('assign_to_id',$employee_id);
		// $m->addCondition('type','Followup');
		$v = $schedule_cal_tab->add('xepan\projects\View_TaskCalendar',['defaultView'=>'agendaDay','title_field'=>'task_name','add_employee_filter'=>false,'default_task_type'=>'Followup'])->addClass('main-box');
		$v->setModel($m);

		

		// My Leads
		$crud = $leads_tab->add('xepan\hr\CRUD',['allow_add'=>false,'allow_edit'=>false,'allow_del'=>false],null,['grid/lead-grid']);
		$mylead = $leads_tab->add('xepan\marketing\Model_Lead');
		$mylead->addCondition('assign_to_id',$employee_id);
		$mylead->setOrder('id','desc');
		$crud->setModel($mylead,['emails_str','contacts_str','name','organization_name_with_name','source','city','type','score','total_visitor','created_by_id','created_by','assign_to_id','assign_to','effective_name','code','organization','existing_associated_catagories','created_at','priority']);
		$crud->add('xepan\base\Controller_Avatar');
		$crud->grid->addPaginator(25);

		// My Opportunity
		// $status=['Open','Qualified','NeedsAnalysis','Quoted','Negotiated','Won','Lost'];

		$opp_m= $oppo_tab->add('xepan\marketing\Model_Opportunity');
		$opp_m->addCondition('assign_to_id',$employee_id);
		$opp_m->addCondition('status',['Open','Qualified','Quoted']);

		$crud = $oppo_tab->add('xepan\hr\CRUD',null,null,['grid/opportunity-grid']);
		$crud->setModel($opp_m,['last_communication','effective_name','lead_id','title','description','status','assign_to_id','fund','discount_percentage','closing_date','city','country'],['organization','last_communication','effective_name','lead','title','description','status','assign_to','fund','discount_percentage','closing_date','city','country']);
		$crud->add('xepan\base\Controller_Avatar',['name_field'=>'lead']);
		if(!$crud->isEditing()){
			$crud->grid->js('click')->_selector('.do-view-opportunity')->univ()->frameURL('Lead Details',[$this->api->url('xepan_marketing_leaddetails'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-lead-id]')->data('lead-id')]);
		}


		$f = $crud->grid->addQuickSearch(['lead','title','description']);

		$f->addField('CheckBox','Open')->js('change',$f->js()->submit());
		$f->addField('CheckBox','Qualified')->js('change',$f->js()->submit());
		// $f->addField('CheckBox','NeedsAnalysis')->js('change',$f->js()->submit());
		$f->addField('CheckBox','Quoted')->js('change',$f->js()->submit());
		// $f->addField('CheckBox','Negotiated')->js('change',$f->js()->submit());
		// $f->addField('CheckBox','Won')->js('change',$f->js()->submit());
		// $f->addField('CheckBox','Lost')->js('change',$f->js()->submit());

		$f->addHook('applyFilter',function($f,$m){
			$show_status=[];
			if($f['Open']) $show_status[]='Open';
			if($f['Qualified']) $show_status[]='Qualified';
			// if($f['NeedsAnalysis']) $show_status[]='NeedsAnalysis';
			if($f['Quoted']) $show_status[]='Quoted';
			// if($f['Won']) $show_status[]='Won';
			// if($f['Lost']) $show_status[]='Lost';
			if(count($show_status))
				$m->addCondition('status',$show_status);
		});

		$crud->grid->addPaginator(25);


		// My Tasks
		$task_assigned_to_me_model = $tasks_tab->add('xepan\projects\Model_Formatted_Task')
	    	->addCondition('type','Task')
	    	->addCondition('is_regular_work',false)
	    	;
	    $field_to_destroy = ['total_duration','is_started','is_running','follower_count','total_comment'/*,'created_by_image'*//*,'assigned_to_image'*/,'related_name','priority_name','assign_employee_status','created_by_employee_status','contact_name','contact_organization'];
	    foreach ($field_to_destroy as $field) {
		    $task_assigned_to_me_model->getElement($field)->destroy();
	    }

	    $task_assigned_to_me_model
	    			->addCondition(
	    				$task_assigned_to_me_model->dsql()->orExpr()
	    					->where('assign_to_id',$employee_id)
	    					->where(
    								$task_assigned_to_me_model->dsql()->andExpr()
    									->where('created_by_id',$employee_id)
    									->where('assign_to_id',null)
	    							)
	    				)->addCondition('type','Task');
	    $task_assigned_to_me_model->setOrder(['updated_at desc','last_comment_time','priority']);
	    $task_assigned_to_me_model->addCondition('status',['Pending','Submitted','Assigned','Inprogress']);

	    $grid = $tasks_tab->add('xepan\projects\View_TaskList',['pass_acl'=>true]);
	    $grid->add('xepan\base\Controller_Avatar',['name_field'=>'created_by','extra_classes'=>'profile-img center-block','options'=>['size'=>50,'display'=>'block','margin'=>'auto'],'float'=>null]);

	    $grid->setModel($task_assigned_to_me_model);
	    $grid->addPaginator(25);
	}
}