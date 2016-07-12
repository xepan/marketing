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
			return "'1,2,3'";
		});

		if($this->app->stickyGET('status'))
			$newsletter->addCondition('status',explode(",",$this->app->stickyGET('status')));
				
		$newsletter->add('xepan\marketing\Controller_SideBarStatusFilter');
		$newsletter->addCondition('is_template',false);
		$crud=$this->add('xepan\hr\CRUD',['action_page'=>'xepan_marketing_newslettertemplate', 'edit_page'=>'xepan_marketing_newsletterdesign'],null,['grid/newsletter-grid']);
		$crud->setModel($newsletter);
		
		$frm=$crud->grid->addQuickSearch(['title']);

		$crud->grid->addHook('formatRow',function($g){			
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