<?php

namespace xepan\marketing;
	
class page_lead extends \Page{
	public $title = "Lead";
	function init(){
		parent::init();

		$lead = $this->add('xepan\marketing\Model_Lead');

		$crud = $this->add('xepan\hr\CRUD',['action_page'=>'xepan_marketing_leaddetails'],null,['grid/lead-grid']);
		$crud->setModel($lead);
		$crud->grid->addPaginator(10);
		$crud->add('xepan\base\Controller_Avatar');
		
		$frm=$crud->grid->addQuickSearch(['name']);
				
		$status=$frm->addField('Dropdown','marketing_category_id')->setEmptyText('Categories');
		$status->setModel('xepan\marketing\MarketingCategory');
		$status->js('change',$frm->js()->submit());

		$frm->addHook('appyFilter',function($f,$m){
			if($frm['marketing_category_id'])
				$m->addCondition('document_id',$f['marketing_category_id']);
		});

		$crud->grid->addColumn('category');
		$crud->grid->addMethod('format_marketingcategory',function($grid,$field){				
				$data = $grid->add('xepan\marketing\Model_Lead_Category_Association')->addCondition('lead_id',$grid->model->id);
				$l = $grid->add('Lister',null,'category',['grid/lead-grid','category_lister']);
				$l->setModel($data);
				
				$grid->current_row_html[$field] = $l->getHtml();
		});

		$crud->grid->addFormatter('category','marketingcategory');
	}
}