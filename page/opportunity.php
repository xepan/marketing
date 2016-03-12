<?php
namespace xepan\marketing;

class page_opportunity extends \Page{
	public $title ="Opportunity";

	function init(){
		parent::init(); 
 
		$opportunity = $this->add('xepan\marketing\Model_Opportunity');
		
		$crud = $this->add('xepan\hr\CRUD',null,null,['grid/opportunity-grid']);
		$crud->setModel($opportunity);		
		$crud->add('xepan\base\Controller_Avatar',['name_field'=>'lead']);
		
		$f = $crud->grid->addQuickSearch(['lead']);
		$dropdown = $f->addField('dropdown','status')->setValueList(['Open'=>'Open','Converted'=>'Converted','Rejected'=>'Rejected'])->setEmptyText('Status');
		$dropdown->js('change',$f->js()->submit());
	}

}