<?php

namespace xepan\marketing;

class Widget_LeadPriority extends \xepan\base\Widget{
	function init(){
		parent::init();

		$this->grid = $this->add('xepan\hr\Grid');
	}

	function recursiveRender(){
		$lead_m = $this->add('xepan\marketing\Model_Lead');

		$lead_m->addExpression('last_landing_response_date_from_lead')->set(function($m,$q){
			$landing_response = $m->add('xepan\marketing\Model_LandingResponse')
									->addCondition('contact_id',$m->getElement('id'))
									->setLimit(1)
									->setOrder('date','desc');
			return $q->expr("[0]",[$landing_response->fieldQuery('date')]);
		});

		$lead_m->addExpression('last_communication_date_from_lead')->set(function($m,$q){
			$communication = $m->add('xepan\communication\Model_Communication')->addCondition('from_id',$m->getElement('id'))->addCondition('direction','In')->setLimit(1)->setOrder('created_at','desc');
			return $q->expr("[0]",[$communication->fieldQuery('created_at')]);
		});


		$lead_m->addExpression('last_communication_date_from_company')->set(function($m,$q){
			$communication = $m->add('xepan\communication\Model_Communication')->addCondition('to_id',$m->getElement('id'))->addCondition('direction','Out')->setLimit(1)->setOrder('created_at','desc');
			return $q->expr("[0]",[$communication->fieldQuery('created_at')]);
		});

		// current date - max from last_landing_from_lead, last_communication_form_lead or last_communication_form_employee
		$lead_m->addExpression('days_ago')->set(function($m,$q){
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
		$lead_m->addExpression('priority')->set(function($m,$q)use($k){
			return $q->expr('[0] * [1] * [2]',[$m->getElement('days_ago'),$m->getElement('score'),$k]);
		});

		$lead_m->addCondition('score','>',0);
		$lead_m->setOrder('last_communication_date_from_company','desc');
		$lead_m->setOrder('last_communication_date_from_lead','desc');
		$lead_m->setOrder('last_landing_response_date_from_lead','desc');
		$lead_m->setOrder('score','desc');
		$lead_m->setOrder('priority','desc');
		$lead_m->setLimit(10);

		$this->grid->setModel($lead_m,['name','last_communication_date_from_company','days_ago','priority']);

		return parent::recursiveRender();
	}
}