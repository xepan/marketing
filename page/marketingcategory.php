<?php
namespace xepan\marketing;

class page_marketingcategory extends \xepan\base\Page{

	public $title = "Marketing Category";
	function init(){
		parent::init();

		$m = $this->add('xepan\marketing\Model_MarketingCategory');

		$crud = $this->add('xepan\hr\CRUD',null,null,['grid/category-grid']);

		$crud->setModel($m,['name','leads_count']);
	    $crud->grid->addQuickSearch(['name']);
	    $crud->grid->addPaginator(50);
	    $crud->add('xepan\base\Controller_MultiDelete');

	    $crud->grid->js(true)->_load('jquery.sparkline.min')->_selector('.sparkline')->sparkline('html', ['enableTagOptions' => true]);
		
		$crud->grid->addHook('formatRow', function($g){
			$s = $this->add('xepan\marketing\Model_MarketingCategory');						
			$s->load($g->model->id);
			
			if($s['system']){
				$g->current_row_html['edit'] = ' ';
				$g->current_row_html['delete'] = ' ';
			}
		});
	}

	function defaultTemplate(){

		return['page/marketingcampaign'];
	}

	function render(){
		$this->app->jui->addStaticInclude('jquery.easypiechart.min');
		parent::render();
	}	
}