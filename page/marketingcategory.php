<?php
namespace xepan\marketing;

class page_marketingcategory extends \xepan\base\Page{

	public $title = "Marketing Category";
	function init(){
		parent::init();

		$m = $this->add('xepan\marketing\Model_MarketingCategory');
		// $association_j = $m->join('lead_category_association.marketing_category_id','id');
		// $association_j->addField('marketing_lead_id','lead_id');
		// $association_j->join('communication.to_id','marketing_lead_id');
		// // $m->_dsql()->group('name');
		// $this->add('Grid')->setModel($m);
		// return;

		// $m->addExpression('weekly_communication')->set(function($m,$q){
		// 	$comm = $m->add('xepan/communication/Model_Communication');
		// 	$comm->_dsql()->del('fields');
		// 	$comm->_dsql()->field('count(*) communication_count');
		// 	$comm->_dsql()->field('to_id');
		// 	$comm->_dsql()->group('to_id');

		// 	return $q->expr("(select GROUP_CONCAT(tmp.communication_count) from [sql] as tmp where tmp.to_id = [0])",[$m->getElement('marketing_lead_id'),'sql'=>$comm->_dsql()]);
		// });

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