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

		$view_lead = $this->add('xepan\hr\Grid',null, 'side',['view\teleleadselector'])->addClass('view-lead-grid');
		$model_lead = $this->add('xepan\marketing\Model_Lead');
		$view_lead->js('reload')->reload();

		$view_lead->addHook('formatRow',function($g){
 			$communication = $this->add('xepan\marketing\Model_TeleCommunication')
									->addCondition('to_id',$g->model->id)
									->setOrder('id','desc')
									->tryLoadAny();

			if($communication['description']){
 				$g->current_row['last_communication']= substr($communication['description'],0,41).'...';
				$g->current_row['date']= $communication['created_at']; 			
			}
 		});
		
		$view_lead->setModel($model_lead, ['name','type','city','contacts_str','score']);
		$view_lead->add('xepan\base\Controller_Avatar',['options'=>['size'=>25,'border'=>['width'=>0]],'name_field'=>'name','default_value'=>'']);
		$view_lead->addPaginator(10);

		$frm = $view_lead->addQuickSearch(['name','contacts_str']);

		$status=$frm->addField('Dropdown','marketing_category_id')->setEmptyText('Categories');
		$status->setModel('xepan\marketing\MarketingCategory');

		$frm->addHook('applyFilter',function($f,$m){
			if($f['marketing_category_id']){
				$cat_asso = $this->add('xepan\marketing\Model_Lead_Category_Association');
				$cat_asso->addCondition('marketing_category_id',$f['marketing_category_id']);
				$m->addCondition('id','in',$cat_asso->fieldQuery('lead_id'));
			}
		});
		
		$status->js('change',$frm->js()->submit());

		$view_lead->js('click')->_selector('.do-view-lead')->univ()->frameURL('Lead Details',[$this->api->url('xepan_marketing_leaddetails'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);

		/*
				FORM FOR ADDING CONVERSATION 
		*/

		$model_telecommunication = $this->add('xepan\marketing\Model_TeleCommunication');
		$view_teleform = $this->add('View',null,'top');
		$view_teleform_url = $this->api->url(null,['cut_object'=>$view_teleform->name]);
		
		$form = $view_teleform->add('Form');
		$form->setLayout('view\teleconversationform');
		

		$lead_name = $form->layout->add('View',null,'name')->set(isset($lead_model)?$lead_model['name']:'No Lead Selected');
		
		// getting contact string to display it as dropdown

		$button = $form->layout->add('Button',null,'btn-opportunity')->set('Opportunities')->addClass('btn btn-sm btn-primary');

		/*
			GRID FOR SHOWING PREVIOUS CONVERSATION 
		*/							

		$view_conversation = $this->add('xepan\communication\View_Lister_Communication',['contact_id'=>$contact_id, 'type' =>'TeleMarketing'],'bottom');

		$model_communication = $this->add('xepan\communication\Model_Communication');
		$model_communication->addCondition(
										$model_communication->dsql()->andExpr()
									  	->where('to_id',$contact_id)
									  	->where('to_id','<>',null));
		$model_communication->setOrder('id','desc');

		$view_conversation->setModel($model_communication);
		$view_conversation->add('Paginator',['ipp'=>1]);

		/*
			JS FOR RELOAD WITH SPECIFIC ID 
		*/
					
		$view_lead->js('click',
			[$view_conversation->js()->reload(['contact_id'=>$this->js()->_selectorThis()->data('id')]),
			$view_teleform->js()->reload(['contact_id'=>$this->js()->_selectorThis()->data('id')])
			])->_selector('.tele-lead');
		
		if($contact_id){
			$form->on('click','.positive-lead',function($js,$data)use($lead_model,$model_communication,$view_lead){
				$this->app->hook('pointable_event',['telemarketing_response',['lead'=>$lead_model,'comm'=>$model_communication,'score'=>true]]);
			$js_array = [
				$js->univ()->successMessage('Positive Marking Done'),
				$view_lead->js()->_selector('.view-lead-grid')->trigger('reload'),
				];
			return $js_array;
			});
			
			$form->on('click','.negative-lead',function($js,$data)use($lead_model,$model_communication,$view_lead){
				$this->app->hook('pointable_event',['telemarketing_response',['lead'=>$lead_model,'comm'=>$model_communication],'score'=>false]);
				$js_array = [
				$js->univ()->successMessage('Negative Marking Done'),
				$view_lead->js()->_selector('.view-lead-grid')->trigger('reload'),

				];
			return $js_array;
			});
		}

		/*
				VIRTUAL PAGE TO SEE AND ADD OPPORTUNITIES 
		*/	

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
	}

	function defaultTemplate(){
		return['page\telemarketing'];
	}
}