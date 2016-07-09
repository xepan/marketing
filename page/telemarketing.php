<?php

namespace xepan\marketing;

class page_telemarketing extends \xepan\base\Page{
	public $title = "Tele Marketing";
	function init(){
		parent::init();
		
		$lead_id = $this->app->stickyGET('lead_id');

		if($lead_id)
			$lead_model = $this->add('xepan\marketing\Model_Lead')->load($lead_id);
		/*
				GRID FOR SHOWING ALL LEAD 
		*/

		$view_lead = $this->add('xepan\hr\Grid',null, 'side',['view\teleleadselector']);
		$model_lead = $this->add('xepan\marketing\Model_Lead');
		
		// $model_lead->addExpression('score')->set(function($m,$q){
		// 	// return "'123'";
		// 	$ps=$m->add('xepan\base\Model_PointSystem');
		// 	$ps->addCondition('contact_id',$q->getField('id'));
		// 	return $ps->sum('score');
		// })->sortable(true);	

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

		$form->addField('Line','title');
		$form->addField('Text','description');
		$form->addField('Line','from_number');
		$form->addField('Line','to_number');
		$form->addSubmit('Add Conversation')->addClass('btn btn-sm btn-primary');

		$button = $form->layout->add('Button',null,'btn-opportunity')->set('Opportunities')->addClass('btn btn-sm btn-primary');

		
		/*
				GRID FOR SHOWING PREVIOUS CONVERSATION 
		*/							

		$model_communication = $this->add('xepan\marketing\Model_TeleCommunication')
									->addCondition('to_id',$lead_id)->setOrder('id','desc')->setLimit(1);
		$view_conversation = $this->add('xepan\hr\CRUD',null, 'bottom',['view\teleconversationlister']);
		$view_conversation->setModel($model_communication,['title','description','to_raw'],['title','description','created_at','from','to_raw']);
		// $view_conversation->setModel($model_communication);
		$view_conversation_url = $this->api->url(null,['cut_object'=>$view_conversation->name]);
		$view_conversation->grid->addPaginator(10);
		$view_conversation->grid->addQuickSearch(['name']);

		
		$view_conversation->grid->addHook('formatRow',function($g){	
			$data = json_decode($g->model['to_raw'],true);
			$g->current_row_html['last_call_no'] = $data[0]['number'];
		});		
		/*
				JS FOR RELOAD WITH SPECIFIC ID 
		*/
		
		$view_lead->on('click','#lead',function($js,$data)use($view_conversation_url,$view_conversation,$view_teleform_url,$view_teleform){
			

			$js_array = [
					$view_conversation->js()->reload(['lead_id'=>$data['id']],null,$view_conversation_url),
					$view_teleform->js()->reload(['lead_id'=>$data['id']],null,$view_teleform_url),

					];
			return $js_array;
		});
		if($lead_id){
			$form->on('click','.positive-lead',function($js,$data)use($lead_model,$model_communication){
				$this->app->hook('pointable_event',['telemarketing_response',['lead'=>$lead_model,'comm'=>$model_communication,'score'=>true]]);
			});
			
			$form->on('click','.negative-lead',function($js,$data)use($lead_model,$model_communication){
				$this->app->hook('pointable_event',['telemarketing_response',['lead'=>$lead_model,'comm'=>$model_communication],'score'=>false]);
			});
		}
		
		
		/*
				FORM SUBMISSION 
		*/

		if($form->isSubmitted()){

			if(!$lead_id){
				return $form->js()->univ()->errorMessage('Please Select A Lead First')->execute();
			}

			$model_telecommunication->unload();

			$model_telecommunication['title'] = $form['title']; 
			$model_telecommunication['description'] = $form['description']; 
			$model_telecommunication['from_id']=$this->app->employee->id;
			$model_telecommunication['to_id'] = $lead_id;
			$model_telecommunication['from_raw'] = json_encode(['name'=>'','number'=>$form['from_number']]); 
			$model_telecommunication['to_raw'] = json_encode([['name'=>'','number'=>$form['to_number']]]); 
			$model_telecommunication->save();

			return $view_conversation->js(true,$form->js()->univ()->reload()->successMessage("Added"))->univ()->reload()->execute();
			  
		}

		/*
				VIRTUAL PAGE TO SEE AND ADD OPPORTUNITIES 
		*/	
 		$button->add('VirtualPage')
			->bindEvent('Opportunities','click')
			->set(function($page){
				$lead_id = $this->app->stickyGET('lead_id');
				if(!$lead_id){
					$page->add('View_Error')->set('Please Select A Lead First');
					return;	
				}
				$opportunity_model = $page->add('xepan\marketing\Model_Opportunity')
									  ->addCondition('lead_id',$lead_id);	
				$page->add('xepan\hr\CRUD',null,null,['grid\miniopportunity-grid'])->setModel($opportunity_model);

			});
	}

	function defaultTemplate(){
		return['page\telemarketing'];
	}
}