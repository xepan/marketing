<?php
namespace xepan\marketing;

class page_opportunity extends \xepan\base\Page{
	public $title ="Opportunity";

	function init(){
		parent::init(); 
 
		$opportunity = $this->add('xepan\marketing\Model_Opportunity');
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
	}

}