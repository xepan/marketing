<?php
namespace xepan\marketing;

class page_marketingcategory extends \xepan\base\Page{

	public $title = "Marketing Category";
	function init(){
		parent::init();

		$m = $this->add('xepan\marketing\Model_MarketingCategory');

		$m->addExpression('weakly_communication')->set(function($m,$q){
			$comm = $m->add('xepan/marketing/Model_Schedule');
			$comm->addExpression('campaign_id',$q->getField('id'));
			$comm->addExpression('date','>',date('Y-m-d',strtotime('-8 week')));
			$comm->_dsql()->del('fields');
			$comm->_dsql()->field('count(*) shcedules_count');
			$comm->_dsql()->field('campaign_id');
			$comm->_dsql()->group('campaign_id');

			return $q->expr("(select GROUP_CONCAT(tmp.shcedules_count) from [sql] as tmp where tmp.campaign_id = [0])",[$q->getField('id'),'sql'=>$comm->_dsql()]);
		});

		$crud = $this->add('xepan\hr\CRUD',null,null,['grid/category-grid']);

		$crud->setModel($m);
	    $crud->grid->addQuickSearch(['name']);
	    $crud->grid->addPaginator(50);

	    $crud->grid->js(true)->_load('jquery.sparkline.min')->_selector('.sparkline')->sparkline('html', ['enableTagOptions' => true]);
	}

	function defaultTemplate(){

		return['page/marketingcampaign'];
	}

	function render(){
		$this->app->jui->addStaticInclude('jquery.easypiechart.min');
		parent::render();
	}	

}