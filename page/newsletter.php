<?php
namespace xepan\marketing;
class page_newsletter extends \xepan\base\Page{
	public $title="Newsletter";
	function init(){
		parent::init();

		if($this->app->stickyGET('compact_grid'))
			$grid_template = ['grid/newslettercompact-grid'];
		else
			$grid_template = ['grid/newsletter-grid'];

		$newsletter = $this->add('xepan\marketing\Model_Newsletter');
		$newsletter->setOrder('created_at','desc');
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
				
		$newsletter->add('xepan\base\Controller_TopBarStatusFilter');
		$newsletter->addCondition('is_template',false);

		$crud=$this->add('xepan\hr\CRUD',['action_page'=>'xepan_marketing_newslettertemplate', 'edit_page'=>'xepan_marketing_newsletterdesign'],null,$grid_template);
		$crud->setModel($newsletter);
		
		$switch_btn = $crud->grid->addButton('Grid View')->addClass('btn btn-primary');

		if($switch_btn->isClicked()){
			$this->js(null,$crud->js()->reload(['compact_grid'=>true]))->univ()->successMessage('wait ...')->execute();
		}

		$crud->grid->addPaginator('10');
		$frm=$crud->grid->addQuickSearch(['title','content_name']);
		
		$marketing_category = $frm->addField('DropDown','marketing_category_id');
		$marketing_category->setModel('xepan\marketing\Model_MarketingCategory');
		$marketing_category->setEmptyText('Select a category');


		$vp = $this->add('VirtualPage');
		$vp->set(function($p){
			$p->app->stickyGET('newsletter_id');

			if($_GET['show_newsletter']){
				$newsletter_model = $this->add('xepan\marketing\Model_Newsletter')->load($_GET['newsletter_id']);
				
				$nv = $p->add('View');
				$nv->template->trySetHTML('Content',$newsletter_model['message_blog']);				
			}else{
				$v = $p->add('View')->setElement('iframe');
				$v->setAttr('src',$p->app->url(null,['show_newsletter'=>1,'cut_page'=>1]))
					->setAttr('width','100%')
					->setAttr('height','475px')
					;
			}
		});	


		$this->on('click','.newsletter-preview',function($js,$data)use($vp){
				return $js->univ()->dialogURL("NEWSLETTER PREVIEW",$this->api->url($vp->getURL(),['newsletter_id'=>$data['id']]),['width'=>'100%','height'=>'500']);
		});


		$vp1 = $this->add('VirtualPage');
		$vp1->set(function($p){
			$l_r = $this->add('xepan\marketing\Model_LandingResponse');
			$l_r->addCondition('content_id',$_GET['newsletter_id']);
			$l_r->setOrder('date','desc');
			$p->add('Grid')->setModel($l_r,['contact','date']);
		});	

		$this->on('click','.newsletter-visit-detail',function($js,$data)use($vp1){
				return $js->univ()->dialogURL("VISIT DETAIL",$this->api->url($vp1->getURL(),['newsletter_id'=>$data['id']]));
		});

		$frm->addHook('applyFilter',function($f,$m){
			if($f['marketing_category_id']){
				$m->addCondition('marketing_category_id',$f['marketing_category_id']);
			}
		});
		
		$marketing_category->js('change',$frm->js()->submit());

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