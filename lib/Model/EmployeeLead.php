<?php

namespace xepan\marketing;

/**
* 
*/
class Model_EmployeeLead extends \xepan\hr\Model_Employee{
	public $from_date;
	public $to_date;
	function init(){
		
		parent::init();
		$this->addCondition('status','Active');

		$this->addExpression('total_lead_created')->set(function($m,$q){

			$lead = $this->add('xepan\marketing\Model_Lead',['table_alias'=>'employee_created_lead']);
				return 	$lead->addCondition('created_by_id',$q->getField('id'))
						->addCondition('created_at','>=',$this->from_date)
						->addCondition('created_at','<',$this->api->nextDate($this->to_date))
						->count();
		});

		$this->addExpression('total_lead_assign_to')->set(function($m,$q){
			// return $q->getField('id');
			$lead = $this->add('xepan\marketing\Model_Lead',['table_alias'=>'employee_assign_lead_to']);
				return 	$lead->addCondition('assign_to_id',$q->getField('id'))
						->addCondition('created_at','>=',$this->from_date)
						->addCondition('created_at','<',$this->api->nextDate($this->to_date))
						->count();
		});

		$this->addExpression('total_lead_assign_by')->set(function($m,$q){
			// return $q->getField('id');
			$lead = $this->add('xepan\marketing\Model_Lead',['table_alias'=>'employee_assign_lead_by']);
				return 	$lead->addCondition('created_by_id',$q->getField('id'))
						->addCondition('assign_to_id','<>',$q->getField('id'))
						->addCondition('created_at','>=',$this->from_date)
						->addCondition('created_at','<',$this->api->nextDate($this->to_date))
						->count();
		});

		$this->addExpression('total_followup')->set(function($m,$q){

			$my_followups_model = $this->add('xepan\projects\Model_Task');
		    return $my_followups_model
		    						->addCondition('assign_to_id',$q->getField('id'))
		    						// ->addCondition('created_by_id',$q->getField('id'))
		    						->addCondition('created_at','>=',$this->from_date)
									->addCondition('created_at','<',$this->api->nextDate($this->to_date))
		    						->addCondition('type','Followup')
		    						->count();
		});

		$this->addExpression('open_opportunity')->set(function($m,$q){

			$opportunity = $this->add('xepan\marketing\Model_Opportunity');
		    return $opportunity->addCondition('assign_to_id',$q->getField('id'))
		    						->addCondition('status','Open')
		    						->addCondition('created_at','>=',$this->from_date)
									->addCondition('created_at','<',$this->api->nextDate($this->to_date))
		    						->count();
		});

		$this->addExpression('qualified_opportunity')->set(function($m,$q){

			$opportunity = $this->add('xepan\marketing\Model_Opportunity');
		    return $opportunity->addCondition('assign_to_id',$q->getField('id'))
		    						->addCondition('status','Qualified')
		    						->addCondition('created_at','>=',$this->from_date)
									->addCondition('created_at','<',$this->api->nextDate($this->to_date))
		    						->count();
		});

		$this->addExpression('needs_analysis_opportunity')->set(function($m,$q){
			$opportunity = $this->add('xepan\marketing\Model_Opportunity');
		    return $opportunity->addCondition('assign_to_id',$q->getField('id'))
		    						// ->addCondition([['created_by_id',$q->getField('id')],['assign_to_id',null]])
		    						->addCondition('status','NeedsAnalysis')
		    						->addCondition('created_at','>=',$this->from_date)
									->addCondition('created_at','<',$this->api->nextDate($this->to_date))
		    						->count();
		});

		$this->addExpression('quoted_opportunity')->set(function($m,$q){
			$opportunity = $this->add('xepan\marketing\Model_Opportunity');
		    return $opportunity->addCondition('assign_to_id',$q->getField('id'))
		    						// ->addCondition([['created_by_id',$q->getField('id')],['assign_to_id',null]])
		    						->addCondition('status','Quoted')
		    						->addCondition('created_at','>=',$this->from_date)
									->addCondition('created_at','<',$this->api->nextDate($this->to_date))
		    						->count();
		});

		$this->addExpression('negotiated_opportunity')->set(function($m,$q){
			$opportunity = $this->add('xepan\marketing\Model_Opportunity');
		    return $opportunity->addCondition('assign_to_id',$q->getField('id'))
		    						// ->addCondition([['created_by_id',$q->getField('id')],['assign_to_id',null]])
		    						->addCondition('status','Negotiated')
		    						->addCondition('created_at','>=',$this->from_date)
									->addCondition('created_at','<',$this->api->nextDate($this->to_date))
		    						->count();
		});

		$this->addExpression('win_opportunity')->set(function($m,$q){
			$opportunity = $this->add('xepan\marketing\Model_Opportunity');
		    return $opportunity->addCondition('assign_to_id',$q->getField('id'))
		    						// ->addCondition([['created_by_id',$q->getField('id')],['assign_to_id',null]])
		    						->addCondition('status','Won')
		    						->addCondition('created_at','>=',$this->from_date)
									->addCondition('created_at','<',$this->api->nextDate($this->to_date))
		    						->count();
		});

		$this->addExpression('loss_opportunity')->set(function($m,$q){
			$opportunity = $this->add('xepan\marketing\Model_Opportunity');
		    return $opportunity->addCondition('assign_to_id',$q->getField('id'))
		    						// ->addCondition([['created_by_id',$q->getField('id')],['assign_to_id',null]])
		    						->addCondition('status','Lost')
		    						->addCondition('created_at','>=',$this->from_date)
									->addCondition('created_at','<',$this->api->nextDate($this->to_date))
		    						->count();
		});
	}
}