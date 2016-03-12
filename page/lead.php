<?php

namespace xepan\marketing;
	
class page_lead extends \Page{
	public $title = "Lead";
	function init(){
		parent::init();

		$lead = $this->add('xepan\marketing\Model_Lead');

		$crud = $this->add('xepan\hr\CRUD',['action_page'=>'xepan_marketing_leaddetails'],null,['grid/lead-grid']);
		$crud->setModel($lead);
		$f=$crud->grid->addQuickSearch(['name']);
				
		$status=$f->addField('Dropdown','category_id')->setEmptyText('All Category');
		$status->setModel('xepan\marketing\MarketingCategory');
		$status->js('change',$f->js()->submit());

		$f->addHook('appyFilter',function($f,$m){
			if($f['category_id'])
				$m->addCondition('marketing_category_id',$f['category_id']);
		});

		$crud->add('xepan\base\Controller_Avatar');
	}
}