<?php

namespace xepan\marketing;

class page_widget_roi extends \xepan\base\Page{
	function init(){
		parent::init();


		$x_axis = $this->app->stickyGET('x_axis');
		$details = $this->app->stickyGET('details');
		$details = json_decode($details,true);
		$start_date = $this->app->stickyGET('start_date');
		$end_date = $this->app->stickyGET('end_date');
		$employee_id = $this->app->stickyGET('employee');

		$employee_m = $this->add('xepan\hr\Model_Employee');
		$employee_m->tryLoadBy('name',$x_axis);

		if(!$employee_m->loaded())
			$employee_m->tryLoadBy('id',$employee_id);

		if(!$employee_m->loaded()){
			$this->add('View')->set('Employee Not Found');
			return;
		}

		$employee_id = $employee_m->id;
		$status = $details['name'];

		$opportunity = $this->add('xepan\marketing\Model_Opportunity');
		$opp_lead_j = $opportunity->opp_j->join('contact.id','lead_id');
		$lead_info = $opp_lead_j->addField('organization');

		// $opportunity = $this->add('xepan\marketing\Model_Opportunity');	

		$opportunity->addCondition('status','Won');
		$opportunity->addCondition('created_by_id',$employee_id);
		$opportunity->addCondition('created_at','>=',$start_date);
		$opportunity->addCondition('created_at','<=',$this->app->nextDate($end_date));

		$grid = $this->add('xepan\hr\Grid',null,null,['page/widget/opportunity-grid']);
		
		$grid->setModel($opportunity,['organization','last_communication','effective_name','lead_id','title','description','status','assign_to_id','fund','discount_percentage','closing_date'],['organization','last_communication','effective_name','lead','title','description','status','assign_to','fund','discount_percentage','closing_date']);
		$grid->addPaginator(10);		
		$grid->add('xepan\base\Controller_Avatar',['name_field'=>'lead']);
		$grid->addQuickSearch(['title','description',$lead_info]);
	}
}