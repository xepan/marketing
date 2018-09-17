<?php

namespace xepan\marketing;

class page_employeeleadassign extends \xepan\base\Page{

	public $title = "Employee Multiple Lead Assign";
	function init(){
		parent::init();

		$this->filter = $this->app->stickyGET('filter');
		$this->execute = $this->app->stickyGET('execute');
		$this->lead_category = $this->app->stickyGET('lead_category');
		$this->currently_assign_to_employee = $this->app->stickyGET('currently_assign_to_employee');
		$this->created_by_employee = $this->app->stickyGET('lead_created_by_employee');
		$this->from_date = $this->app->stickyGET('from_date');
		$this->to_date = $this->app->stickyGET('to_date');

		$this->sub_type_1 = $this->app->stickyGET('last_communication_subtype_1');
		$this->sub_type_2 = $this->app->stickyGET('last_communication_subtype_2');
		$this->sub_type_3 = $this->app->stickyGET('last_communication_subtype_3');
		$this->assign_to_employee = $this->app->stickyGET('assign_to_employee');
		$this->leads = $this->app->stickyGET('leads');


		$subtype_config = $this->add('xepan\communication\Model_Config_SubType');
		$subtype_config->tryLoadAny();
		$sub_type_1_value = explode(",", $subtype_config['sub_type']);
		$sub_type_1_value = array_combine($sub_type_1_value, $sub_type_1_value);

		$sub_type_2_value = explode(",", $subtype_config['calling_status']);
		$sub_type_2_value = array_combine($sub_type_2_value, $sub_type_2_value);

		$sub_type_3_value = explode(",", $subtype_config['sub_type_3']);
		$sub_type_3_value = array_combine($sub_type_3_value, $sub_type_3_value);

		$lead = $this->add('xepan\marketing\Model_Lead');
		$lead->addCondition('status','Active');
		if($this->app->branch->id)
			$lead->addCondition('branch_id',$this->app->branch->id);

		$form = $this->add('Form');
		$form->add('xepan\base\Controller_FLC')
		->showLables(true)
		->addContentSpot()
		->makePanelsCoppalsible(true)
		->layout([
				'lead_category'=>'Selecting Leads~c1~3',
				'lead_created_by_employee'=>'c2~3',
				'currently_assign_to_employee'=>'c3~3',
				'created_between'=>'c4~3',
				'last_communication_subtype_1'=>'c15~4',
				'last_communication_subtype_2'=>'c16~4',
				'last_communication_subtype_3'=>'c17~4',
				// 'leads~select leads'=>'c8~12',
				'assign_to_employee'=>'c9~6',
				'FormButtons~&nbsp;'=>'c10~6',
			]);

		$field_cat = $form->addField('xepan\base\DropDown','lead_category');
		$cat_model = $this->add('xepan\marketing\Model_MarketingCategory');
		if($this->app->branch->id)
			$cat_model->addCondition('branch_id',$this->app->branch->id);
		
		$field_cat->setModel($cat_model);
		$field_cat->setEmptyText('Please Select ...');

		$field_created_by_emp = $form->addField('xepan\hr\Employee','lead_created_by_employee');
		$field_currently_assign_to_emp = $form->addField('xepan\hr\Employee','currently_assign_to_employee');
		$field_comm_subtype_1 = $form->addField('xepan\base\DropDown','last_communication_subtype_1');
		$field_comm_subtype_1->setValueList($sub_type_1_value)->setEmptyText('Please Select ...');
		$field_comm_subtype_2 = $form->addField('xepan\base\DropDown','last_communication_subtype_2');
		$field_comm_subtype_2->setValueList($sub_type_2_value)->setEmptyText('Please Select ...');
		$field_comm_subtype_3 = $form->addField('xepan\base\DropDown','last_communication_subtype_3');
		$field_comm_subtype_3->setValueList($sub_type_3_value)->setEmptyText('Please Select ...');

		$field_date_range = $form->addField('DateRangePicker','created_between');
		// $field_leads = $form->addField('xepan\base\DropDown','leads')->enableMultiSelect();
		// $field_leads->setModel('xepan\marketing\Model_Lead');

		$field_assign_to_emp = $form->addField('xepan\hr\Employee','assign_to_employee');
		$field_assign_to_emp->validate('required');

		$view_btn = $form->addSubmit('View Leads')->addClass('btn btn-warning');
		$submit_btn = $form->addSubmit('Assign Leads')->addClass('btn btn-primary');

		$v = $this->add('View');
		$lead_model = $this->add('xepan\marketing\Model_Lead');
		if($this->filter){

			$this->applyCondition($lead_model);

			if($this->execute AND $lead_model->count()->getOne()){
				$ids = $lead_model->_dsql()->del('fields')->field($lead_model->getElement('id'))->getAll();
				$ids = iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($ids)),false);
				
				$this->app->db->dsql()->table('contact')
						->set('assign_to_id',$this->assign_to_employee)
						->set('assign_at',$this->app->now)
						->where('id','in',$ids)
						->update();
			}
			
			$g = $v->add('xepan\base\Grid');
			$g->setModel($lead_model,['name','created_by','assign_to','created_at','last_subtype_1_value','last_subtype_2_value','last_subtype_3_value']);
			$g->addPaginator(25);

		}


		if($form->isSubmitted()){
			$param = array_merge([
					'filter'=>1,
					'from_date'=>$field_date_range->getStartDate()?:0,
					'to_date'=>$field_date_range->getEndDate()?:0
				],$form->get());
			
			if($form->isClicked($view_btn)){
				$v->js()->reload($param)->execute();
			}

			if($form->isClicked($submit_btn)){
				$param = array_merge($param,['execute'=>1]);
				$v->js()->reload($param)->execute();
			}

			$form->js(null,$form->js()->univ()->successMessage('Multiple Lead Assigned'))->reload()->execute();
		}

	}


	function applyCondition($model){
		if($this->lead_category){
			$lead_assoc = $model->join('lead_category_association.lead_id','id');
			$lead_assoc->addField('lead_category_id','marketing_category_id');
			$model->addCondition('lead_category_id',$this->lead_category);
			$model->_dsql()->group('lead_id');
		}

		if($this->currently_assign_to_employee){
			$model->addCondition('assign_to_id',$this->currently_assign_to_employee);
		}
		if($this->created_by_employee){
			$model->addCondition('created_by_id',$this->created_by_employee);
		}
		if($this->from_date){			
			$model->addCondition('created_at','>=',$this->from_date);
		}
		if($this->to_date){	
			$model->addCondition('created_at','<',$this->app->nextDate($this->to_date));
		}

		$model->addExpression('last_subtype_1_value')->set(function($m,$q){
			$comm = $this->add('xepan\communication\Model_Communication',['table_alias'=>'commsub1']);
			$comm->addCondition([['from_id',$m->getElement('id')],['to_id',$m->getElement('id')],['related_contact_id',$m->getElement('id')]]);
			$comm->addCondition([['sub_type','<>',null],['calling_status','<>',null],['sub_type_3','<>',null]])
					->setOrder('id','desc')
					->setLimit(1);
			return $q->expr('[0]',[$comm->fieldQuery('sub_type')]);
		});
		if($this->sub_type_1){
			$model->addCondition('last_subtype_1_value',$this->sub_type_1);
		}

		$model->addExpression('last_subtype_2_value')->set(function($m,$q){
			$comm = $this->add('xepan\communication\Model_Communication',['table_alias'=>'commsub2']);
			$comm->addCondition([['from_id',$m->getElement('id')],['to_id',$m->getElement('id')],['related_contact_id',$m->getElement('id')]]);
			$comm->addCondition([['sub_type','<>',null],['calling_status','<>',null],['sub_type_3','<>',null]])
					->setOrder('id','desc')
					->setLimit(1);
			return $q->expr('[0]',[$comm->fieldQuery('calling_status')]);
		});
		if($this->sub_type_2){
			$model->addCondition('last_subtype_2_value',$this->sub_type_2);
		}

		$model->addExpression('last_subtype_3_value')->set(function($m,$q){
			$comm = $this->add('xepan\communication\Model_Communication',['table_alias'=>'commsub3']);
			$comm->addCondition([['from_id',$m->getElement('id')],['to_id',$m->getElement('id')],['related_contact_id',$m->getElement('id')]]);
			$comm->addCondition([['sub_type','<>',null],['calling_status','<>',null],['sub_type_3','<>',null]])
					->setOrder('id','desc')
					->setLimit(1);
			return $q->expr('[0]',[$comm->fieldQuery('sub_type_3')]);
		});
		if($this->sub_type_3){
			$model->addCondition('last_subtype_3_value',$this->sub_type_3);
		}
		return $model;
	}
}