<?php
namespace xepan\marketing;
class page_socialcontent extends \xepan\base\Page{
	public $title="Social Content";
	function init(){
		parent::init();
		

		$social = $this->add('xepan\marketing\Model_SocialPost');

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

		if($this->app->stickyGET('status'))
			$social->addCondition('status',explode(",",$this->app->stickyGET('status')));
				
		$social->add('xepan\marketing\Controller_SideBarStatusFilter');
		
		$crud=$this->add('xepan\hr\CRUD',['action_page'=>'xepan_marketing_addsocialpost'],null,['grid/social-grid']);
		$crud->setModel($social);
		$frm=$crud->grid->addQuickSearch(['title']);
		
		
		$crud->grid->addHook('formatRow',function($g){
			$g->current_row_html['msg'] = $g->model['message_blog'];

			$source_data = explode(",",$g->model['source_graph_data']);
			$source_values=[];
			$source_labels =[];
			foreach ($source_data as $dt) {
				$dt = explode("/", $dt);
				$source_values[] = $dt[0];
				$source_labels[] = $dt[1];
			}

			$g->current_row_html['source_graph'] = $g->model['source_graph'];
			$g->js(true)->_selector('.sparkline[data-id='.$g->model->id.']')->sparkline($source_values, ['enableTagOptions' => true,'tooltipFormat'=>'{{offset:offset}} ({{percent.1}}%)','tooltipValueLookups'=>['offset'=>$source_labels]]);
		});
		$crud->grid->js(true)->_load('jquery.sparkline.min');
	}
}