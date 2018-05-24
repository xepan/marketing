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

		$campaign->add('xepan\base\Controller_TopBarStatusFilter');
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
			if($g->model['remaining_duration'] <= 0){
				$g->current_row_html['defect'] ='Expired'; 	
			}elseif($g->model['has_schedule'] == false){
				$g->current_row_html['defect'] ='Schedule?';
			}elseif($g->model['content_not_approved'] == true){
				$g->current_row_html['defect'] ='Approve Content';
			}elseif($g->model['has_social_schedule'] == true And $g->model['socialuser_count'] == false){
				$g->current_row_html['defect'] ='User?';
			}elseif($g->model['has_newsletter_schedule'] == true And $g->model['category_count'] == false){
				$g->current_row_html['defect'] ='Category?'; 	
			}else{
				$g->current_row_html['defect'] = 'Visits : '.$g->model['total_visitor'];
			}

			if($g->model['status'] == 'Draft'){
				$g->current_row_html['box'] = 'gray-box'; 	
				$g->current_row_html['bg'] = 'gray-bg';	
			}
			elseif($g->model['status'] == 'Submitted'){
				$g->current_row_html['box'] = 'yellow-box'; 	
				$g->current_row_html['bg'] = 'yellow-bg';	
			}
			elseif($g->model['status'] == 'Approved'){
				$g->current_row_html['box'] = 'green-box'; 	
				$g->current_row_html['bg'] = 'green-bg';	
			}
			elseif($g->model['status']== 'Onhold'){
				$g->current_row_html['box'] = 'emerald-box'; 	
				$g->current_row_html['bg'] = 'emerald-bg';			
			}else{
				$g->current_row_html['box'] = 'purple-box'; 	
				$g->current_row_html['bg'] = 'purple-bg';
			}

			$g->current_row['url'] = "?page=xepan_marketing_scheduledetail&campaign_id=".$g->model->id;

			if($g->model['campaign_type'] == "subscription"){
				$g->current_row['campaign_icon_class'] = "fa fa-list-alt ";
			}
			if($g->model['campaign_type'] == "campaign"){
				$g->current_row['campaign_icon_class'] = "fa fa-calendar";
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
		
		$sent_btn = $crud->grid->addButton('Sent Newsletters')->addClass('btn btn-primary');

		$p2=$this->add('VirtualPage');
		$p2->set(function($p){
			$newsletter_sent_m = $p->add('xepan\marketing\Model_Communication_Newsletter');
			$newsletter_sent_m->setOrder('created_at','desc');
			$newsletter_sent_m->getElement('created_at')->sortable(true);
			$newsletter_sent_m->getElement('status')->sortable(true);
			$newsletter_sent_m->getElement('to')->sortable(true);
			$newsletter_sent_m->getElement('title')->sortable(true);

			$grid = $p->add('xepan\base\Grid');
			$grid->setModel($newsletter_sent_m,['to','title','created_at','status']);
			$grid->addPaginator(50);
			$frm = $grid->addQuickSearch(['to','title','status',],['to','title','status']);
		});
		
		if($sent_btn->isClicked()){
			$this->js()->univ()->frameURL('Newsletters Sent',$p2->getUrl())->execute();
		}

		$unsubscribe_btn = $crud->grid->addButton('Unsubscribes')->addClass('btn btn-primary');

		$p3=$this->add('VirtualPage');
		$p3->set(function($p){
			$model_unsubscribe = $p->add('xepan\marketing\Model_Unsubscribe');
			$grid = $p->add('xepan\base\Grid');

			$grid->setModel($model_unsubscribe);
		});
		
		if($unsubscribe_btn->isClicked()){
			$this->js()->univ()->frameURL('Unsubscribes',$p3->getUrl())->execute();
		}

	}

	function render(){
		$this->app->jui->addStaticInclude('jquery.easypiechart.min');
		parent::render();
	}	
}