<?php

namespace xepan\marketing;

class page_campaign extends \xepan\base\Page{
	public $title="Campaign";
	function init(){
		parent::init();	

		$campaign = $this->add('xepan\marketing\Model_Campaign');

		$campaign->addExpression('source_graph_data')->set(function($m,$q){
			$lr = $m->add('xepan\marketing\Model_LandingResponse');
			$lr->_dsql()->del('fields');
			$lr->_dsql()->field('count(*) visits');
			$lr->_dsql()->field('campaign_id');
			$lr->_dsql()->field('type');
			$lr->_dsql()->group('campaign_id');
			$lr->_dsql()->group('type');

			return $q->expr("(select GROUP_CONCAT(concat(tmp.visits,'/',tmp.type)) from [sql] as tmp where tmp.campaign_id = [0])",[$q->getField('id'),'sql'=>$lr->_dsql()]);
		});
		
		$campaign->addExpression('timing_graph_data')->set(function($m,$q){
			$lr = $m->add('xepan\marketing\Model_LandingResponse');
			$lr->_dsql()->del('fields');
			$lr->_dsql()->field('count(*) visits');
			$lr->_dsql()->field('campaign_id');
			$lr->_dsql()->field('HOUR(date) timeslot');
			$lr->_dsql()->group('campaign_id');
			$lr->_dsql()->group('HOUR(date)');

			return $q->expr("(select GROUP_CONCAT(concat(tmp.visits,'/',tmp.timeslot)) from [sql] as tmp where tmp.campaign_id = [0])",[$q->getField('id'),'sql'=>$lr->_dsql()]);
		});

		if($this->app->stickyGET('status'))
			$campaign->addCondition('status',explode(",",$this->app->stickyGET('status')));

		$landing_response = $this->add('xepan\marketing\Model_LandingResponse');
		$response = $landing_response->getRows();

		$campaign->add('xepan\marketing\Controller_SideBarStatusFilter');
		$crud=$this->add('xepan\hr\CRUD',['action_page'=>'xepan_marketing_addcampaign'],null,['grid/campaign-grid']);
		$crud->setModel($campaign);
		$crud->grid->js(true)->_load('jquery.sparkline.min')->_selector('.sparkline')->sparkline('html', ['enableTagOptions' => true]);
		$frm=$crud->grid->addQuickSearch(['title']);
		
		$vp1 = $this->add('VirtualPage');
		$vp1->set(function($p){
			$l_r = $this->add('xepan\marketing\Model_LandingResponse');
			$l_r->addCondition('campaign_id',$_GET['campaign_id']);
			$l_r->setOrder('date','desc');
			$p->add('Grid')->setModel($l_r,['contact','date']);
		});	

		$this->on('click','.campaign-visit-detail',function($js,$data)use($vp1){
				return $js->univ()->dialogURL("VISIT DETAIL",$this->api->url($vp1->getURL(),['campaign_id'=>$data['id']]));
		});

		$crud->grid->addHook('formatRow',function($g){
			if($g->model['campaign_type']==='subscription'){
				$g->current_row['url'] = "?page=xepan_marketing_subscriberschedule&campaign_id=".$g->model->id;
			}else{
				$g->current_row['url'] = "?page=xepan_marketing_schedule&campaign_id=".$g->model->id;
			}

			$source_data = explode(",",$g->model['source_graph_data']);
			$source_values=[];
			$source_labels =[];
			foreach ($source_data as $dt) {
				$dt = explode("/", $dt);
				$source_values[] = $dt[0];
				$source_labels[] = $dt[1];
			}

			$timing_data = explode(",", $g->model['timing_graph_data']);
			$timing_values=[];
			$timing_labels = [];
			foreach ($timing_data as $dt) {
				$dt= explode("/", $dt);
				$timing_values[] =$dt[0];
				$timing_labels[] =$dt[1];
			}

			$g->current_row_html['source_graph'] = $g->model['source_graph'];
			$g->js(true)->_selector('.sparkline.source_graph[data-id='.$g->model->id.']')->sparkline($source_values, ['enableTagOptions' => true,'tooltipFormat'=>'{{offset:offset}} ({{percent.1}}%)','tooltipValueLookups'=>['offset'=>$source_labels]]);
			$g->js(true)->_selector('.sparkline.timing_graph[data-id='.$g->model->id.']')->sparkline($timing_values, ['enableTagOptions' => true,'tooltipFormat'=>'{{offset:offset}} ({{percent.1}}%)','tooltipValueLookups'=>['offset'=>$timing_labels]]);
		});
		$crud->grid->js(true)->_load('jquery.sparkline.min');														
	}

	function render(){
		$this->app->jui->addStaticInclude('jquery.easypiechart.min');
		parent::render();
	}	
}