<?php

namespace xepan\marketing;

class page_leaddetails extends \xepan\base\Page {
	
	public $title ='Lead Details';
	public $breadcrumb=['Home'=>'index','Lead'=>'xepan_marketing_lead','Details'=>'#'];


	function init(){
		parent::init();

		$action = $this->api->stickyGET('action')?:'view';
		$lead= $this->add('xepan\marketing\Model_Lead');

		
		$lead->addExpression('weekly_communication')->set(function($m,$q){
			$comm = $m->add('xepan/communication/Model_Communication');
			// $comm->addCondition('sent_on','>',date('Y-m-d',strtotime('-8 week')));
			$comm->_dsql()->del('fields');
			$comm->_dsql()->field('count(*) communication_count');
			$comm->_dsql()->field('to_id');
			$comm->_dsql()->group('to_id');

			return $q->expr("(select GROUP_CONCAT(tmp.communication_count) from [sql] as tmp where tmp.to_id = [0])",[$m->getElement('id'),'sql'=>$comm->_dsql()]);
		});

		$lead->tryLoadBy('id',$this->api->stickyGET('contact_id'));

		// ===== Edit as and when required for variour type of contacts 
		// and put taeir respective namespace here 
		// is a hack for now, later we will throw hook to register namespace with type

		if($lead->loaded()){
			if($lead['type']=='Employee') $lead->namespace='xepan\hr';
			if($lead['type']=='Affiliate') $lead->namespace='xepan\hr';
			if($lead['type']=='Customer') $lead->namespace='xepan\commerce';
			if($lead['type']=='Supplier') $lead->namespace='xepan\commerce';
			if($lead['type']=='Warehouse') $lead->namespace='xepan\commerce';
			if($lead['type']=='OutsourceParty') $lead->namespace='xepan\production';
		}

		if($action=="add"){
			$base_validator = $this->add('xepan\base\Controller_Validator');

			$form = $this->add('Form',['validator'=>$base_validator],'contact_view_full_width',['form/empty']);
			$form->setLayout(['page/leadprofile','contact_view_full_width']);			
			$form->setModel($lead,['first_name','last_name','address','city','country_id','state_id','pin_code','organization','post','website','source','remark','assign_to_id']);
			$form->addField('line','email_1')->validate('email');
			$form->addField('line','email_2');
			$form->addField('line','email_3');
			$form->addField('line','email_4');
			
			$form->addField('line','contact_no_1');
			$form->addField('line','contact_no_2');
			$form->addField('line','contact_no_3');
			$form->addField('line','contact_no_4');
			$form->addField('Checkbox','want_to_add_next_lead')->set(true);

			$country_field =  $form->getElement('country_id');
			$state_field = $form->getElement('state_id');

			if($cntry_id = $this->app->stickyGET('country_id')){			
				$state_field->getModel()->addCondition('country_id',$cntry_id);
			}

			$country_field->js('change',$state_field->js()->reload(null,null,[$this->app->url(null,['cut_object'=>$state_field->name]),'country_id'=>$country_field->js()->val()]));

			$categories_field = $form->addField('DropDown','category');
			$categories_field->setModel($this->add('xepan\marketing\Model_MarketingCategory'));
			$categories_field->addClass('multiselect-full-width');
			$categories_field->setAttr(['multiple'=>'multiple']);
			$categories_field->setEmptyText("Please Select");

			$form->addSubmit('Add');

			if($form->isSubmitted()){

				if(!$form['source'])
					$form->displayError('source','mandatory');
				
				try{
					$this->api->db->beginTransaction();
					$form->save();
					$new_lead_model = $form->getModel();
					
					if($form['email_1']){
						$email = $this->add('xepan\base\Model_Contact_Email');
						$email['contact_id'] = $new_lead_model->id;
						$email['head'] = "Official";
						$email['value'] = $form['email_1'];
						$email->save();
					}

					if($form['email_2']){
						$email = $this->add('xepan\base\Model_Contact_Email');
						$email['contact_id'] = $new_lead_model->id;
						$email['head'] = "Official";
						$email['value'] = $form['email_2'];
						$email->save();
					}

					if($form['email_3']){
						$email = $this->add('xepan\base\Model_Contact_Email');
						$email['contact_id'] = $new_lead_model->id;
						$email['head'] = "Personal";
						$email['value'] = $form['email_3'];
						$email->save();
					}
					if($form['email_4']){
						$email = $this->add('xepan\base\Model_Contact_Email');
						$email['contact_id'] = $new_lead_model->id;
						$email['head'] = "Personal";
						$email['value'] = $form['email_4'];
						$email->save();
					}

					// Contact Form
					if($form['contact_no_1']){
						$phone = $this->add('xepan\base\Model_Contact_Phone');
						$phone['contact_id'] = $new_lead_model->id;
						$phone['head'] = "Official";
						$phone['value'] = $form['contact_no_1'];
						$phone->save();
					}

					if($form['contact_no_2']){
						$phone = $this->add('xepan\base\Model_Contact_Phone');
						$phone['contact_id'] = $new_lead_model->id;
						$phone['head'] = "Official";
						$phone['value'] = $form['contact_no_2'];
						$phone->save();
					}

					if($form['contact_no_3']){
						$phone = $this->add('xepan\base\Model_Contact_Phone');
						$phone['contact_id'] = $new_lead_model->id;
						$phone['head'] = "Personal";
						$phone['value'] = $form['contact_no_3'];
						$phone->save();
					}
					if($form['contact_no_4']){
						$phone = $this->add('xepan\base\Model_Contact_Phone');
						$phone['contact_id'] = $new_lead_model->id;
						$phone['head'] = "Personal";
						$phone['value'] = $form['contact_no_4'];
						$phone->save();
					}

					// Category Association
					if($form['category']){
						$categories = explode(",",$form['category']);
						foreach ($categories as $key => $cat_id) {
							if(!is_numeric($cat_id))
								continue;

							$cat_asso_model = $this->add('xepan\marketing\Model_Lead_Category_Association');
							$cat_asso_model['lead_id'] = $new_lead_model->id;
							$cat_asso_model['marketing_category_id'] = $cat_id;
							$cat_asso_model->save();
						}
					}					
					$this->api->db->commit();
				}catch(\Exception_StopInit $e){

		        }catch(\Exception $e){
		            $this->api->db->rollback();
		            throw $e;
		        }	

		        if($form['want_to_add_next_lead']){
		        	$form->js(null,$form->js()->reload())->univ()->successMessage('Lead Created Successfully')->execute();
		        }
				$form->js(null,$form->js()->univ()->successMessage('Lead Created Successfully'))->univ()->redirect($this->app->url(null,['action'=>"edit",'contact_id'=>$new_lead_model->id]))->execute();
			}
			// Temporary off view qsp add form 
			// $lead_view = $this->add('xepan\base\View_Contact',['acl'=>'xepan\marketing\Model_Lead','view_document_class'=>'xepan\hr\View_Document','page_reload'=>($action=='add')],'contact_view_full_width');
			// $lead_view->document_view->effective_template->del('im_and_events_andrelation');
			// $lead_view->document_view->effective_template->del('email_and_phone');
			// $lead_view->document_view->effective_template->del('avatar_wrapper');
			// $lead_view->document_view->effective_template->del('contact_since_wrapper');
			// $lead_view->document_view->effective_template->del('send_email_sms_wrapper');
			// $lead_view->document_view->effective_template->del('online_status_wrapper');
			// $lead_view->document_view->effective_template->del('contact_type_wrapper');
			// $lead_view->setStyle(['width'=>'50%','margin'=>'auto']);
			$this->template->del('other_details');
		}else{
			$this->template->del('contact_view_full_width');
			$lead_view = $this->add('xepan\base\View_Contact',['acl'=>'xepan\marketing\Model_Lead','view_document_class'=>'xepan\hr\View_Document'],'contact_view');
			$lead_view->setModel($lead);
		}


		$detail = $this->add('xepan\hr\View_Document',['action'=> $action,'id_field_on_reload'=>'contact_id'],'details',['view/details']);
		$detail->setModel($lead,['assign_to','assign_to_id','source','marketing_category','communication','opportunities','remark','weekly_communication'],['assign_to_id','source','remark']);//,'marketing_category_id','communication','opportunities'
		if(($action != 'view')){				
			$detail->form->getElement('assign_to_id')->getModel()->addCondition('type','Employee');
		}

		if($lead->loaded()){
			
			$opportunities_tab = $this->add('xepan\hr\View_Document',['action'=> $action,'id_field_on_reload'=>'contact_id'],'opportunity',['view/opp']);
			$o = $opportunities_tab->addMany('opportunity',null,'opportunity',['grid/addopportunity-grid']);
			if($action != 'view')
				$o->grid->addQuickSearch(['title']);
			$o->setModel($lead->ref('Opportunities'),['title','description','status','assign_to_id','fund','discount_percentage','closing_date'])->setOrder('created_at','desc');

			$activity_view = $this->add('xepan\base\Grid',['no_records_message'=>'No activity found'],'activity',['view/activity/activity-grid']);
			if($action != 'view')
				$activity_view->addQuickSearch(['activity']);
			$activity_view->add('xepan\base\Paginator',null,'Paginator');

			$activity=$this->add('xepan\base\Model_Activity')->setOrder('created_at','desc');
			$activity->addCondition('related_contact_id',$_GET['contact_id']);
			$activity->tryLoadAny();
			$activity_view->setModel($activity);	


			/*
			*	
			*	Lead <=> Category association form
			*	
			*/
			
			$model_assoc_category = $this->add('xepan\marketing\Model_MarketingCategory');

			if($action=='view'){
				$base= $detail;
				
				$asso = $lead->ref('xepan\marketing\Lead_Category_Association');

				$category_assoc_grid = $base->add('xepan\base\Grid',['show_header'=>false],'marketing_category');
				$category_assoc_grid->setModel($asso,['marketing_category'])
							        ->_dsql()->group('marketing_category_id');
			}

			else{

				$base = $detail->form->layout;

				$cat_ass_field = $base->addField('DropDown','ass_cat')->set($lead->getAssociatedCategories());
				$cat_ass_field->setAttr(['multiple'=>'multiple']);
				$cat_ass_field->setModel('xepan\marketing\Model_MarketingCategory');

				$detail->form->onSubmit(function($frm){
					$lead_model = $this->add('xepan\marketing\Model_lead')->load($_GET['contact_id']);	
					$lead_model->removeAssociateCategory();

					$selected_categories = [];
					$selected_categories = explode(',', $frm['ass_cat']);
												
					foreach ($selected_categories as $cat) {
						if(!$cat)
							break;														
						$lead_model->associateCategory($cat);
					}

					$frm->save();
					$frm->js(null,$this->js()->univ()->successMessage('Saved'))->reload()->execute();	
				});
			
			}

		}
		
		$this->js(true)->_load('jquery.sparkline.min')->_selector('.sparkline')->sparkline('html', ['enableTagOptions' => true, 'chartRangeMin' =>0]);
	}

	function defaultTemplate(){
		return ['page/leadprofile'];
	}

	function render(){
		// $this->app->jui->addStaticInclude('jquery.easypiechart.min');
		parent::render();
	}	
}
