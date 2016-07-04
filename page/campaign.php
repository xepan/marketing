<?php
namespace xepan\marketing;
class page_campaign extends \xepan\base\Page{
	public $title="Campaign";
	function init(){
		parent::init();	

		$campaign = $this->add('xepan\marketing\Model_Campaign');
		if($this->app->stickyGET('status'))
			$campaign->addCondition('status',explode(",",$this->app->stickyGET('status')));
		
		$campaign->addExpression('completed_percentage')->set(function($m, $q){
			return $m->dsql()->expr("ROUND(([1]-[0])/[1]*100,0)",[$m->getElement('total_postings'),$m->getElement('remaining')]);
		});

		$landing_response = $this->add('xepan\marketing\Model_LandingResponse');
		$response = $landing_response->getRows();

		$campaign->add('xepan\marketing\Controller_SideBarStatusFilter');
		$crud=$this->add('xepan\hr\CRUD',['action_page'=>'xepan_marketing_addcampaign'],null,['grid/campaign-grid']);
		$crud->setModel($campaign);
		$crud->grid->js(true)->_load('jquery.sparkline.min')->_selector('.sparkline')->sparkline('html', ['enableTagOptions' => true]);
		$frm=$crud->grid->addQuickSearch(['title']);
														
	}

	function render(){
		$this->app->jui->addStaticInclude('jquery.easypiechart.min');
		parent::render();
	}	
}