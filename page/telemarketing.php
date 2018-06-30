<?php

namespace xepan\marketing;

class page_telemarketing extends \xepan\base\Page{
	public $title = "Tele Marketing";
	function init(){
		parent::init();
		
		$contact_id = $this->app->stickyGET('contact_id');
		
		if($contact_id)
			$lead_model = $this->add('xepan\marketing\Model_Lead')->load($contact_id);
		/*
			GRID FOR SHOWING ALL LEAD 
		*/

		$view_lead = $this->add('xepan\hr\CRUD',['grid_options'=>['fixed_header'=>false]],'left_side')->addClass('view-lead-grid');

		$model_lead = $this->add('xepan\marketing\Model_Lead');
		$model_lead->addCondition('status','Active');

		$model_lead->getElement('effective_name')->caption('Name');
		$view_lead->grid->addHook('formatRow',function($g){

 			$communication = $this->add('xepan\marketing\Model_TeleCommunication')
									->addCondition('to_id',$g->model->id)
									->setOrder('id','desc')
									->tryLoadAny();
			$title = "";

			$name_html = '<a title="'.$title.'" href="#" data-id="'.$g->model['id'].'" class="do-view-lead">'.$g->model['effective_name']."</a>";
			if(trim($g->model['organization']))
				$name_html .= "<br/>".$g->model['organization'];

			$name_html .= "<br/>".$g->model['city'].", ".$g->model['state'].",".$g->model['country'];
			$name_html .= "<br/>".$g->model['contacts_comma_seperated'];
			// last communication
			if($communication['description']){
				$title = "Last communication on date: ".$communication['created_at']."(".strip_tags($communication['description']).")";
				$name_html .= '<br/><span>Last Comm: '.$communication['title'].'</span>'.'<span> '.$communication['created_at'].'</span>';
			}

			$name_html .= "<br/> Days Ago: ".$g->model['days_ago']." Priority:".$g->model['priority']." Score: ".$g->model['score'];
			$g->current_row_html['effective_name'] = '<div title="'.$title.'" data-id="'.$g->model->id.'">'.$name_html.'</div>';
			$g->current_row_html['detail'] = '<div data-id="'.$g->model->id.'" class="tele-lead fa fa-phone btn btn-sm btn-primary"></div>';
 		});
		
		$view_lead->setModel($model_lead, ['priority','effective_name','organization','type','city','contacts_comma_seperated','score','status','state','county','last_communication','days_ago']);
		$view_lead->grid->addColumn('detail');
		// $view_lead->add('xepan\base\Controller_Avatar',['options'=>['size'=>25,'border'=>['width'=>0]],'name_field'=>'name','default_value'=>'']);

		$view_lead->grid->addPaginator(25);
		$view_lead->grid->addFormatter('effective_name','Wrap');
		$field = $model_lead->getActualFields();
		$not_remove = ['effective_name'];
		foreach ($field as $name) {
			if(in_array($name, $not_remove)) continue;
			$view_lead->grid->removeColumn($name);
		}
		$view_lead->grid->removeColumn('action');
		$view_lead->grid->removeAttachment();

		// quick filter
		$frm = $view_lead->grid->addQuickSearch(['effective_name','organization','contacts_comma_seperated','score']);
		$status = $frm->addField('Dropdown','marketing_category_id')->setEmptyText('Select Categories');
		$status->setModel('xepan\marketing\MarketingCategory');
		$frm->addHook('applyFilter',function($f,$m){
			if($f['marketing_category_id']){
				$cat_asso = $this->add('xepan\marketing\Model_Lead_Category_Association');
				$cat_asso->addCondition('marketing_category_id',$f['marketing_category_id']);
				$m->addCondition('id','in',$cat_asso->fieldQuery('lead_id'));
			}

			switch ($f['sort_by']) {
				case 'priority_asc':
					$m->setOrder('priority','asc');
					break;
				case 'priority_desc':
					$m->setOrder('priority','desc');
					break;
				case 'score_asc':
					$m->setOrder('score','asc');
					break;
				case 'score_desc':
					$m->setOrder('score','desc');
					break;
				case 'last_communication_asc':
					$m->setOrder('last_communication','asc');
					break;
				case 'last_communication_desc':
					$m->setOrder('last_communication','desc');
					break;
			}
		});
		$status->js('change',$frm->js()->submit());
		$sort_by_field = $frm->addField('Dropdown','sort_by')
			->setEmptyText('Sort By ..')
			->setValueList([
				'priority_asc'=>'Priority Asc',
				'priority_desc'=>'Priority Desc',
				'score_asc'=>'Score Asc',
				'score_desc'=>'Score Desc',
				'last_communication_asc'=>'Last Communication Asc',
				'last_communication_desc'=>'Last Communication Desc'
			]);
		$sort_by_field->js('change',$frm->js()->submit());


		$view_lead->grid->js('click')->_selector('.do-view-lead')->univ()->frameURL('Lead Details',[$this->api->url('xepan_marketing_leaddetails'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);

		// redirect to other extended view
		$list_view_btn = $view_lead->grid->add('Button',null,'grid_buttons')->set('Detail View')->addClass('btn btn-info');
		$list_view_btn->js('click')->univ()->location($this->app->url('xepan_marketing_telemarketinglistview'));


		$right_side_view = $this->add('View',null,'right_side');

		/*
				VIRTUAL PAGE TO SEE AND ADD OPPORTUNITIES 
		*/	

		if($contact_id){
			$button = $right_side_view->add('Button')->set('Opportunities')->addClass('btn btn-sm btn-primary');
	 		$button->add('VirtualPage')
				->bindEvent('Opportunities','click')
				->set(function($page){
					$contact_id = $this->app->stickyGET('contact_id');
					if(!$contact_id){
						$page->add('View_Error')->set('Please Select A Lead First');
						return;	
					}
					$opportunity_model = $page->add('xepan\marketing\Model_Opportunity')
										  ->addCondition('lead_id',$contact_id);	
					$page->add('xepan\hr\CRUD',null,null,['grid\miniopportunity-grid'])->setModel($opportunity_model,['title','description','status','assign_to_id','fund','discount_percentage','closing_date'],['title','description','status','assign_to_id','fund','discount_percentage','closing_date']);

			});

			$view_communication = $right_side_view->add('View');
			$comm = $view_communication->add('xepan\communication\View_Communication');
			$comm->setCommunicationsWith($lead_model);
			$comm->showCommunicationHistory(true);

		}else{
			$right_side_view->add('View_Error')->set('Please Select lead to view details');
		}

		$view_lead->grid->js('click',
			[
				$right_side_view->js()->reload(['contact_id'=>$this->js()->_selectorThis()->data('id')])
			])->_selector('.tele-lead');

	}

	function defaultTemplate(){
		return['page\telemarketing'];
	}
}