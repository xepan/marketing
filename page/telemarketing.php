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

		$view_lead = $this->add('xepan\hr\CRUD',['allow_add'=>false,'grid_options'=>['fixed_header'=>false]], 'side',['view\teleleadselector'])->addClass('view-lead-grid');
		$model_lead = $this->add('xepan\marketing\Model_Lead');
		$model_lead->addCondition('status','Active');
		$view_lead->js('reload')->reload();

		$view_lead->grid->addHook('formatRow',function($g){
 				$communication = $this->add('xepan\marketing\Model_TeleCommunication')
									->addCondition('to_id',$g->model->id)
									->setOrder('id','desc')
									->tryLoadAny();

			if($communication['description']){
 				$g->current_row['last_communication']= substr($communication['description'],0,41).'...';
				$g->current_row['date']= $communication['created_at']; 			
			}
 		});
		
		$view_lead->setModel($model_lead, ['priority','effective_name','type','city','contacts_str','emails_str','score','status']);
		$view_lead->add('xepan\base\Controller_Avatar',['options'=>['size'=>25,'border'=>['width'=>0]],'name_field'=>'name','default_value'=>'']);
		$view_lead->grid->addPaginator(10);

		$frm = $view_lead->grid->addQuickSearch(['effective_name','contacts_str','emails_str','score']);

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

		$list_view_btn = $view_lead->grid->add('Button',null,'grid_buttons')->set('List View')->addClass('btn btn-info');

		$list_view_btn->js('click')->univ()->location($this->app->url('xepan_marketing_telemarketinglistview'));

		/*
				FORM FOR ADDING CONVERSATION 
		*/

		$model_telecommunication = $this->add('xepan\marketing\Model_TeleCommunication');
		$view_teleform = $this->add('View',null,'top');
		$view_teleform_url = $this->api->url(null,['cut_object'=>$view_teleform->name]);
		
		// opportunity, filter form
		$form = $view_teleform->add('Form');
		$form->setLayout('view\teleconversationform');
		$type_field = $form->addField('xepan\base\DropDown','communication_type');
		$type_field->setAttr(['multiple'=>'multiple']);
		$type_field->setValueList(['TeleMarketing'=>'TeleMarketing','Email'=>'Email','Support'=>'Support','Call'=>'Call','Newsletter'=>'Newsletter','SMS'=>'SMS','Personal'=>'Personal']);
		$form->addField('search');
		$form->addSubmit('Filter')->addClass('btn btn-primary btn-block');
		

		$lead_name = $form->layout->add('View',null,'name')->set(isset($lead_model)?$lead_model['name']:'No Lead Selected');

		$button = $form->layout->add('Button',null,'btn-opportunity')->set('Opportunities')->addClass('btn btn-sm btn-primary');

		/*
			GRID FOR SHOWING PREVIOUS CONVERSATION 
		*/							

		$view_conversation = $this->add('xepan\communication\View_Lister_Communication',['contact_id'=>$contact_id, 'type' =>'TeleMarketing'],'bottom');

		$model_communication = $this->add('xepan\communication\Model_Communication');
		// $model_communication->addCondition(
		// 								$model_communication->dsql()->andExpr()
		// 							  	->where('to_id',$contact_id)
		// 							  	->where('to_id','<>',null));
		
		$model_communication->addCondition(
				$model_communication->dsql()->orExpr()
							->where(
									$model_communication->dsql()->andExpr()
									->where('from_id','<>',null)
									->where('from_id',$contact_id)
								)
							->where(
									$model_communication->dsql()->andExpr()
										->where('to_id','<>',null)
										->where('to_id',$contact_id)
								)
					);

		$model_communication->setOrder('id','desc');


		// FILTERS
		if($_GET['comm_type']){			
			$model_communication->addCondition('communication_type',explode(",", $_GET['comm_type']));
		}
		
		// IN CASE YOU WANT TO SHOW ONLY TELEMARKETING COMMUNICATION, UNCOMMENT THIS LINE 
		// if($this->app->stickyGET('view_telecommunication')){
		// 	$model_communication->addCondition('communication_type','TeleMarketing');
		// }

		if($search = $this->app->stickyGET('search')){			
			$model_communication->addExpression('Relevance')->set('MATCH(title,description,communication_type) AGAINST ("'.$search.'")');
			$model_communication->addCondition('Relevance','>',0);
 			$model_communication->setOrder('Relevance','Desc');
		}

		$view_conversation->setModel($model_communication)->setOrder('created_at','desc');
		$view_conversation->add('Paginator',['ipp'=>10]);

		
		$temp = ['TeleMarketing','Email','Support','Call','Newsletter','SMS','Personal'];
		$type_field->set($_GET['comm_type']?explode(",", $_GET['comm_type']):$temp)->js(true)->trigger('changed');
		
		/*
			JS FOR RELOAD WITH SPECIFIC ID 
		*/
					
		$view_lead->js('click',
			[$view_conversation->js()->reload(['contact_id'=>$this->js()->_selectorThis()->data('id'),'view_telecommunication'=>true]),
			$view_teleform->js()->reload(['contact_id'=>$this->js()->_selectorThis()->data('id')])
			])->_selector('.tele-lead');
		
		if($contact_id){					
			// submitting filter form
			if($form->isSubmitted() AND $contact_id){												
				$view_conversation->js()->reload(['comm_type'=>$form['communication_type'],'search'=>$form['search']])->execute();
			}
			
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