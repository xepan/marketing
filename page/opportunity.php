<?php
namespace xepan\marketing;

class page_opportunity extends \xepan\base\Page{
	public $title ="Opportunity";

	function init(){
		parent::init(); 
 
		$opportunity = $this->add('xepan\marketing\Model_Opportunity');
		$opp_lead_j = $opportunity->opp_j->join('contact.id','lead_id');
		$x = $opp_lead_j->addField('organization');

		$opportunity->setOrder('created_at','desc');
		$opportunity->addExpression('weakly_communication')->set(function($m,$q){
			$comm = $m->add('xepan/communication/Model_Communication');
			$comm->_dsql()->del('fields');
			$comm->_dsql()->field('count(*) communication_count');
			$comm->_dsql()->field('to_id');
			$comm->_dsql()->group('to_id');

			return $q->expr("(select GROUP_CONCAT(tmp.communication_count) from [sql] as tmp where tmp.to_id = [0])",[$m->getElement('lead_id'),'sql'=>$comm->_dsql()]);
		});

		$opportunity->addExpression('last_communication')->set(function($m,$q){
			$lead = $this->add('xepan\marketing\Model_Lead');
			$lead->addCondition('id',$m->getElement('lead_id'));
			$lead->setLimit(1);
			return $lead->fieldQuery('last_communication');
		});	

		$watchable = $this->app->stickyGET('watchable');
		$status = $this->app->stickyGET('status');
		if($watchable){
			$opportunity->addCondition('status','<>',['Won','Lost']);
		}
		else{
			// throw new \Exception($status, 1);
			$opportunity->addCondition('status',$status);
		}

		$opportunity->add('xepan\marketing\Controller_SideBarStatusFilter');

		$crud = $this->add('xepan\hr\CRUD',null,null,['grid/opportunity-grid']);
		
		if($crud->isEditing()){
			$crud->form->setLayout('form\opportunity');
		}	

		$crud->setModel($opportunity,['organization','last_communication','effective_name','lead_id','title','description','status','assign_to_id','fund','discount_percentage','closing_date'],['organization','last_communication','effective_name','lead','title','description','status','assign_to','fund','discount_percentage','closing_date']);
		$crud->grid->addPaginator(10);		
		$crud->add('xepan\base\Controller_MultiDelete');		
		$crud->add('xepan\base\Controller_Avatar',['name_field'=>'lead']);
		
		$f = $crud->grid->addQuickSearch(['lead','title','description',$x]);
		$dropdown = $f->addField('dropdown','status')->setValueList(['Open'=>'Open','Converted'=>'Converted','Rejected'=>'Rejected'])->setEmptyText('Status');
		$dropdown->js('change',$f->js()->submit());

		if(!$crud->isEditing()){
			$crud->grid->js('click')->_selector('.do-view-opportunity')->univ()->frameURL('Lead Details',[$this->api->url('xepan_marketing_leaddetails'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-lead-id]')->data('lead-id')]);
		}

		$crud->grid->js(true)->_load('jquery.sparkline.min')->_selector('.sparkline')->sparkline('html', ['enableTagOptions' => true, 'chartRangeMin' =>0]);
	}
}