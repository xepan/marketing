<?php
namespace xepan\marketing;

class page_opportunity extends \xepan\base\Page{
	public $title ="Opportunity";

	function init(){
		parent::init(); 
 
		$opportunity = $this->add('xepan\marketing\Model_Opportunity');
		$opportunity->addExpression('weakly_communication')->set(function($m,$q){
			$comm = $m->add('xepan/communication/Model_Communication');
			// $comm->addCondition('sent_on','>',date('Y-m-d',strtotime('-8 week')));
			$comm->_dsql()->del('fields');
			$comm->_dsql()->field('count(*) communication_count');
			$comm->_dsql()->field('to_id');
			$comm->_dsql()->group('to_id');

			return $q->expr("(select GROUP_CONCAT(tmp.communication_count) from [sql] as tmp where tmp.to_id = [0])",[$m->getElement('lead_id'),'sql'=>$comm->_dsql()]);
		});	

		if($status = $this->app->stickyGET('status'))
			$opportunity->addCondition('status',$status);
		$opportunity->add('xepan\marketing\Controller_SideBarStatusFilter');

		$crud = $this->add('xepan\hr\CRUD',null,null,['grid/opportunity-grid']);
		$crud->setModel($opportunity);
		$crud->grid->addPaginator(10);		
		$crud->add('xepan\base\Controller_Avatar',['name_field'=>'lead']);
		
		$f = $crud->grid->addQuickSearch(['lead']);
		$dropdown = $f->addField('dropdown','status')->setValueList(['Open'=>'Open','Converted'=>'Converted','Rejected'=>'Rejected'])->setEmptyText('Status');
		$dropdown->js('change',$f->js()->submit());

		if(!$crud->isEditing()){
			$crud->grid->js('click')->_selector('.do-view-opportunity')->univ()->frameURL('Lead Details',[$this->api->url('xepan_marketing_leaddetails'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-lead-id]')->data('lead-id')]);
		}

		$crud->grid->js(true)->_load('jquery.sparkline.min')->_selector('.sparkline')->sparkline('html', ['enableTagOptions' => true, 'chartRangeMin' =>0]);
	}
}