<?php
namespace xepan\marketing;
class page_socialcontent extends \xepan\base\Page{
	public $title="Social Content";
	function init(){
		parent::init();
		

		$social = $this->add('xepan\marketing\Model_SocialPost');
		$social->setOrder('created_at','desc');
		$social->addExpression('source_graph_data')->set(function($m,$q){
			$lr = $m->add('xepan\marketing\Model_LandingResponse');
			$lr->_dsql()->del('fields');
			$lr->_dsql()->field('count(*) visits');
			$lr->_dsql()->field('content_id');
			$lr->_dsql()->field('type');
			$lr->_dsql()->group('content_id');
			$lr->_dsql()->group('type');

			return $q->expr("(select GROUP_CONCAT(concat(tmp.visits,'/',tmp.type)) from [sql] as tmp where tmp.content_id = [0])",[$q->getField('id'),'sql'=>$lr->_dsql()]);
		});

		$social->addExpression('timing_graph_data')->set(function($m,$q){
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
			$social->addCondition('status',explode(",",$this->app->stickyGET('status')));
				
		$social->add('xepan\base\Controller_TopBarStatusFilter');
		
		$crud=$this->add('xepan\hr\CRUD',['action_page'=>'xepan_marketing_addsocialpost'],null,['grid/social-grid']);
		$crud->setModel($social);
		$crud->grid->addPaginator('10');
		
		$frm=$crud->grid->addQuickSearch(['title']);

		$marketing_category = $frm->addField('DropDown','marketing_category_id');
		$marketing_category->setModel('xepan\marketing\Model_MarketingCategory');
		$marketing_category->setEmptyText('Select a category');	
		
		$frm->addHook('applyFilter',function($f,$m){
			if($f['marketing_category_id']){
				$m->addCondition('marketing_category_id',$f['marketing_category_id']);
			}
		});
		
		$marketing_category->js('change',$frm->js()->submit());

		$crud->grid->addHook('formatRow',function($g){
			$model_attachment = $this->add('xepan\base\Model_Document_Attachment')->addCondition('document_id',$g->model->id);
    		$model_attachment->tryLoadAny();
    		$g->current_row_html['first_image'] = $model_attachment['file'];


			$g->current_row_html['msg'] = $g->model['message_blog'];

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