<?php
namespace xepan\marketing;
class page_newsletter extends \xepan\base\Page{
	public $title="Newsletter";
	function init(){
		parent::init();

		$newsletter = $this->add('xepan\marketing\Model_Newsletter');

		$newsletter->addExpression('source_graph_data')->set(function($m,$q){
			return "'1,2,3'";
		});
		
		$newsletter->addExpression('timing_graph_data')->set(function($m,$q){
			$lr = $m->add('xepan\marketing\Model_LandingResponse');
			$lr->_dsql()->del('fields');
			$lr->_dsql()->field('count(*) visits');
			$lr->_dsql()->field('content_id');
			$lr->_dsql()->field('HOUR(date) timeslot');
			$lr->_dsql()->group('content_id');
			$lr->_dsql()->group('HOUR(date)');

			return $q->expr("(select GROUP_CONCAT(concat(tmp.visits,'/',tmp.timeslot)) from [sql] as tmp where tmp.content_id = [0])",[$q->getField('id'),'sql'=>$lr->_dsql()]);
		});

		if($this->app->stickyGET('status'))
			$newsletter->addCondition('status',explode(",",$this->app->stickyGET('status')));
				
		$newsletter->add('xepan\marketing\Controller_SideBarStatusFilter');
		$newsletter->addCondition('is_template',false);
		$crud=$this->add('xepan\hr\CRUD',['action_page'=>'xepan_marketing_newslettertemplate', 'edit_page'=>'xepan_marketing_newsletterdesign'],null,['grid/newsletter-grid']);
		$crud->setModel($newsletter);
		
		$frm=$crud->grid->addQuickSearch(['title','content_name']);

		$vp = $this->add('VirtualPage');
		$vp->set(function($p){
			$newsletter_model = $this->add('xepan\marketing\Model_Newsletter')->load($_GET['newsletter_id']);
			
			$nv = $p->add('View');
			$nv->template->trySetHTML('Content',$newsletter_model['message_blog']);
		});	


		$this->on('click','.newsletter-preview',function($js,$data)use($vp){
				return $js->univ()->dialogURL("NEWSLETTER PREVIEW",$this->api->url($vp->getURL(),['newsletter_id'=>$data['id']]));
		});

		$crud->grid->addHook('formatRow',function($g){
			$com = $this->add('xepan\communication\Model_Communication');
			$com->addCondition('related_document_id',$g->model->id);
			$sent = $com->count()->getOne();
			$g->current_row_html['total_sent'] = $sent;
			$g->current_row_html['source_graph'] = 'Subject: '.substr($g->model['title'],0,49).'...';
			
			if($g->model['total_visitor'] == 0)
				$g->current_row_html['success_ratio'] = '0';
			else{
				$sr = round(($sent/$g->model['total_visitor'])*100);
				$g->current_row_html['success_ratio'] = $sr;
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
}