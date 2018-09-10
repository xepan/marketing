<?php

namespace xepan\marketing;
	
class page_lead extends \xepan\base\Page{
	public $title = "Lead";
	public $content=null;
	public $model_class;
	public $crud;
	public $filter_form;

	function page_index(){

		$task_subtype_m = $this->add('xepan\projects\Model_Config_TaskSubtype');
		$task_subtype_m->tryLoadAny();
		$this->task_subtype = explode(",",$task_subtype_m['value']);
		$this->task_subtype = array_combine($this->task_subtype, $this->task_subtype);


		$vp = $this->add('VirtualPage');
		$vp->set(function($p){
			try{
				$lead = $this->add('xepan\marketing\Model_Lead',['addOtherInfo'=>true])->load($_POST['pk']);
				$lead->ref('xepan\marketing\Lead_Category_Association')->deleteAll();
				foreach ($_POST['value']?:[] as $catagory_id) {
					$this->add('xepan\marketing\Model_Lead_Category_Association')
						->set('lead_id',$_POST['pk'])
						->set('marketing_category_id',$catagory_id)
						->saveAndUnload();
				}
			}catch(\Exception $e){
				http_response_code(400);
				echo $e->getMessage();
			}
			exit;
			
		});
		
		if($this->model_class){
			$lead = $this->add($this->model_class,['addOtherInfo'=>true]);
			
		}else{
			$lead = $this->add('xepan\marketing\Model_Lead',['addOtherInfo'=>true]);
		}

		// $lead->getElement('days_ago')->destroy();
		// $lead->getElement('last_communication')->destroy();
		// $lead->getElement('last_landing_response_date_from_lead')->destroy();
		// $lead->getElement('last_communication_date_from_lead')->destroy();
		// $lead->getElement('last_communication_date_from_company')->destroy();


		if($src = $this->app->stickyGET('source'))
			$lead->addCondition('source',$src);
		
		if($strt = $this->app->stickyGET('start_date'))
			$lead->addCondition('created_at','>=',$strt);
		
		if($end = $this->app->stickyGET('end_date'))
			$lead->addCondition('created_at','<=',$this->app->nextDate($end));

		if($category_id = $this->app->stickyGet('category_id')){
			$lead_assoc = $lead->join('lead_category_association.lead_id','id');
			$lead_assoc->addField('lead_category_id','marketing_category_id');

			$lead->addCondition('lead_category_id',$category_id);
			$lead->_dsql()->group('lead_id');
		}

		if($status = $this->app->stickyGET('status'))
			$lead->addCondition('status',$status);

		$lead->addExpression('existing_associated_catagories')->set(function($m,$q){
			$x = $m->add('xepan\marketing\Model_Lead_Category_Association',['table_alias'=>'lead_cat_assos']);
			return $x->addCondition('lead_id',$q->getField('id'))->_dsql()->del('fields')->field($q->expr('group_concat([0])',[$x->getElement('marketing_category_id')]));
		});

		$lead->addExpression('organization_name_with_name')
					->set($lead->dsql()
						->expr('CONCAT(IFNULL([0],"")," ::[ ",IFNULL([1],"")," ]")',
							[$lead->getElement('first_name'),
								$lead->getElement('organization')]))
					->sortable(true);

		$lead->add('xepan\base\Controller_TopBarStatusFilter');
		// $lead->setOrder('total_visitor','desc');

		// $emp_other_info_config_m = $this->add('xepan\base\Model_Config_ContactOtherInfo');
		// $emp_other_info_config_m->addCondition('for','Lead')->tryLoadAny();
		// $other_fields = array_map('trim',explode(",",$emp_other_info_config_m['contact_other_info_fields']));
		$other_fields = $lead->otherInfoFields;

		$this->crud = $crud = $this->add('xepan\hr\CRUD',null,null,['grid/lead-grid']);

		$this->formLayout($crud,$lead, $other_fields);

		$crud->setModel($lead,['first_name','last_name','organization','address','pin_code','city','country_id','state_id','remark','source','assign_to_id','emails_str','contacts_str'],['emails_str','contacts_str','name','organization_name_with_name','source','city','type','score','total_visitor','created_by_id','created_by','assign_to_id','assign_to','assign_at','effective_name','code','organization','existing_associated_catagories','created_at','priority','branch_id'])->setOrder('created_at','desc');
		$export = $crud->grid->add('misc\Export');
		
		$crud->grid->addHook('formatRow',function($g){
			if(!$g->model['assign_to_id']) $g->current_row_html['assign_at'] = "";

		});
		if($crud->isEditing('edit')){
			$cats =$crud->form->model->getAssociatedCategories();
			$crud->form->getElement('categories')->set($cats);

			$crud->form->getElement('emails')->set(str_replace('<br/>', ', ',trim($crud->form->model['emails_str'])));
			$crud->form->getElement('numbers')->set(str_replace('<br/>', ', ',trim($crud->form->model['contacts_str'])));

			$i=1;
			foreach ($other_fields as $of) {
				$value = $this->add('xepan\base\Model_Contact_Other')
					->addCondition('contact_id',$crud->form->model->id)
					->addCondition('head',$of)
					->tryLoadAny();
				if($crud->form->hasElement($this->app->normalizeName($of))){
					$crud->form->getElement($this->app->normalizeName($of))->set($value['value']);
				}
			}
		}

		$crud->grid->addPaginator(50);
		$crud->add('xepan\base\Controller_MultiDelete');

		if(!$crud->isEditing()){
			$catagories = $this->add('xepan\marketing\Model_MarketingCategory');
			$value =[];
			foreach ($catagories as $cc) {
				$value[]=['value'=>$cc->id,'text'=>$cc['name']];
			}

			$quick_edit_permission =false;

			if($this->app->auth->model->isSuperUser()) $quick_edit_permission = true;

			$crud->grid->js(true)->_load('bootstrap-editable.min')->_css('libs/bootstrap-editable')->_selector('.catagory-associated')->editable(
				[
				'url'=>$vp->getURL(),
				'limit'=> 3,
				'source'=> $value,
				'disabled'=> !$quick_edit_permission
				]);
		}

		$grid=$crud->grid;
		$grid->addClass('grab-lead-grid');
		$grid->js('reload')->reload();

		$crud->add('xepan\base\Controller_Avatar');
		
		$this->filter_form = $frm = $grid->addQuickSearch(['name','organization','emails_str','contacts_str','score']);
	
		$category_filter_field = $frm->addField('Dropdown','marketing_category_id')->setEmptyText('Categories');
		$category_filter_field->setModel('xepan\marketing\MarketingCategory');

		if($_GET['category_id'])
			$category_filter_field->js(true)->val($_GET['category_id']);

		$source_type = $frm->addField('Dropdown','source_type')->setEmptyText('Please Select Source');
		$source_model = $this->add('xepan\marketing\Model_Config_LeadSource');
		$source_model->tryLoadAny();
		$source_array = explode(",",$source_model['lead_source']);
		$source_type->setValueList(array_combine($source_array,$source_array));

		// employee filter created by
		$emp_field = $frm->addField('Dropdown','filter_employee_id');
		$emp_field->setModel('xepan\hr\Model_Employee')->addCondition('status','Active');
		$emp_field->setEmptyText('Created By Employee');

		// employee filter assign to
		$emp_assign_field = $frm->addField('Dropdown','filter_employee_assign_to_id');
		$emp_assign_field->setModel('xepan\hr\Model_Employee')->addCondition('status','Active');
		$emp_assign_field->setEmptyText('Assign to Employee');

		$frm->addHook('applyFilter',function($f,$m){
			if($f['marketing_category_id']){
				$cat_asso = $this->add('xepan\marketing\Model_Lead_Category_Association');
				$cat_asso->addCondition('marketing_category_id',$f['marketing_category_id']);
				$m->addCondition('id','in',$cat_asso->fieldQuery('lead_id'));
			}
			if($f['source_type']){
				$m->addCondition('source',$f['source_type']);
			}

			if($f['filter_employee_id']){
				$m->addCondition('created_by_id',$f['filter_employee_id']);
			}

			if($f['filter_employee_assign_to_id']){
				$m->addCondition('assign_to_id',$f['filter_employee_assign_to_id']);
			}

		});
		
		// $category_filter_field->js('change',$grid->js()->reload(null,null,[$this->app->url('.'),'category_id'=>$category_filter_field->js()->val()]));
		$category_filter_field->js('change',$frm->js()->submit());
		$source_type->js('change',$frm->js()->submit());
		$emp_field->js('change',$frm->js()->submit());
		$emp_assign_field->js('change',$frm->js()->submit());


		// $grid->addColumn('category');
		// $grid->addMethod('format_marketingcategory',function($grid,$field){				
		// 		$data = $grid->add('xepan\marketing\Model_Lead_Category_Association')->addCondition('lead_id',$grid->model->id);
		// 		$l = $grid->add('Lister',null,'category',['grid/lead-grid','category_lister']);
		// 		$l->setModel($data);
				
		// 		$grid->current_row_html[$field] = $l->getHtml();
		// });

		// $grid->addFormatter('category','marketingcategory');
		$grid->js(true)->_load('jquery.sparkline.min')->_selector('.sparkline')->sparkline('html', ['enableTagOptions' => true]);
		if(!$crud->isEditing()){
			$grid->js('click')->_selector('.do-view-lead')->univ()->frameURL('Lead Details',[$this->api->url('xepan_marketing_leaddetails'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
			$grid->js('click')->_selector('.do-view-lead-visitor')->univ()->frameURL('Total Visits',[$this->api->url('xepan_marketing_leadvisitor'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
			$grid->js('click')->_selector('.do-view-lead-score')->univ()->frameURL('Total Score',[$this->api->url('xepan_marketing_leadscore'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
		}

		$btn = $grid->addButton('Grab')->addClass('btn btn-primary');
		$btn->js('click',$this->js()->univ()->frameURL('Data Grabber',$this->app->url('./grab')));

		/**			
		CSV Importer
		*/
		$import_btn=$grid->addButton('Import CSV')->addClass('btn btn-primary');
		$import_btn->setIcon('ui-icon-arrowthick-1-n');

		$import_btn->js('click')
			->univ()
			->frameURL(
					'Import CSV',
					$this->app->url('./import')
					);

	}

	function formLayout($crud,$model, $other_fields){
		if(!$crud->isEditing()) return;
		$model->getElement('state_id')->display(['form'=>'xepan\base\Basic']);
		$model->getElement('country_id')->display(['form'=>'xepan\base\Basic']);

		$t = $model->getElement('assign_to_id')->getModel();
		$t->addCondition('type','Employee');

		$config_m = $this->add('xepan\communication\Model_Config_SubType');
		$config_m->tryLoadAny();

		$layout_array=[
					'first_name'=>'General Information~c1~4',
					'last_name'=>'c2~4',
					'organization'=>'c3~4',
					'city'=>'c4~4', // closed to make panel default collapsed
					'country_id~'=>'c5~4',
					'country'=>'c5',
					'state_id~'=>'c6~4',
					'state'=>'c6',
					'emails'=>'c7~12~comma seperated emails',
					'numbers'=>'c8~12~comma seperated numbers',
					'remark'=>'c9~12',
					'source'=>'c10~4',
					'assign_to_id~Assigned to Employee'=>'c11~4',
					'categories'=>'c12~4',
					'score~Score (Is Lead Positive or Negative)'=>'c13~2~or leave as it is for nutral',
					'score_buttons~'=>'c14~3',
					
					'address'=>'Extended Info~a1~12',
					'pin_code'=>'a2~12'];

		

		$i=1;
		foreach ($other_fields as $of) {
			$layout_array[$this->app->normalizeName($of).'~'.$of]='o'.($i++).'~6';
		}
		
		$layout_array=array_merge($layout_array,[
					'communication_type'=>'Initial Communication~x1~12~closed',
					'sub_type~'.$config_m['sub_type_1_label_name']?:"Product/ Service/ Related To"=>'x2~4',
					'calling_status~'.$config_m['sub_type_2_label_name']?:"Communication Result"=>'x3~4',
					'sub_type_3~'.$config_m['sub_type_3_label_name']?:"Communication Remark"=>'x4~4',
					'call_direction'=>'x5~4',
					'email_to'=>'x41~12',
					'cc_mails'=>'x42~12',
					'bcc_mails'=>'x43~12',
					'title'=>'x15~12',
					'body'=>'x6~12',
					'from_email'=>'x7~6',
					'from_phone'=>'x8~6',
					'from_person'=>'x9~6',
					'called_to'=>'x10~6',
					'from_number'=>'x11~6',
					'sms_to'=>'x12~6',
					'sms_settings'=>'x13~6',
					'follow_up'=>'f1~12',
					'followup_assign_to'=>'f2~4',
					'starting_at'=>'f3~3',
					'followup_type'=>'f31~3',
					'existing_schedule'=>'f32~2',
					'description'=>'f4~12',
					'set_reminder'=>'r1~12',
					'remind_via'=>'r2~6',
					'notify_to'=>'r3~6',
					'reminder_time'=>'r4~4',
					// 'force_remind'=>'r5~12',
					'snooze_duration'=>'r6~4',
					'remind_unit'=>'r7~4',

				]);

		if($crud->isEditing('edit')){
			unset($layout_array['communication_type']);unset($layout_array['sub_type']);
			unset($layout_array['calling_status']);unset($layout_array['call_direction']);
			unset($layout_array['title']);unset($layout_array['body']);
			unset($layout_array['from_email']);unset($layout_array['from_phone']);
			unset($layout_array['called_to']);unset($layout_array['from_number']);
			unset($layout_array['sms_to']);
			unset($layout_array['email_to']);unset($layout_array['cc_mails']);
			unset($layout_array['bcc_mails']);unset($layout_array['from_person']);
			unset($layout_array['follow_up']);unset($layout_array['sms_settings']);
			unset($layout_array['followup_assign_to']);unset($layout_array['starting_at']);
			unset($layout_array['description']);
			unset($layout_array['score~Score (Is Lead Positive or Negative)']);
		}

		$crud->form->add('xepan\base\Controller_FLC')
			->showLables(true)
			->makePanelsCoppalsible(true)
			->addContentSpot()
			->layout($layout_array);

		$form = $crud->form;

		$emails_field = $form->addField('emails');
		$number_field = $form->addField('numbers');
		$categories_field = $form->addField('xepan\base\DropDown','categories');
		$categories_field->setModel($this->add('xepan\marketing\Model_MarketingCategory'));
		$categories_field->enableMultiSelect();
		// $categories_field->setEmptyText("Please Select");

		if($crud->isEditing('edit')){		
			$model->addHook('afterLoad',function($m)use($crud,$emails_field,$number_field,$categories_field,$other_fields){
				

			});
		}

		if($crud->isEditing('add')){

			// SCORE BUTTONS START
			$score_field = $form->addField('hidden','score')->set('0');
			$set = $form->layout->add('ButtonSet',null,'score_buttons');
			$up_btn = $set->add('Button')->set('+10')->addClass('btn');
			$down_btn = $set->add('Button')->set('-10')->addClass('btn');
			$up_btn->js('click',[$score_field->js()->val(10),$down_btn->js()->removeClass('btn-danger'),$this->js()->_selectorThis()->addClass('btn-success')]);
			$down_btn->js('click',[$score_field->js()->val(-10),$up_btn->js()->removeClass('btn-success'),$this->js()->_selectorThis()->addClass('btn-danger')]);

			$sub_type_array = explode(",",$config_m['sub_type']);

			$type_field = $form->addField('dropdown','communication_type');
			$type_field->setEmptyText('Please select communication type');
			$type_field->setValueList(['Email'=>'Email','Call'=>'Call','TeleMarketing'=>'TeleMarketing','Personal'=>'Personal','Comment'=>'Comment','SMS'=>'SMS']);

			$sub_type_field = $form->addField('dropdown','sub_type')->setEmptyText('Please Select');
			$sub_type_field->setValueList(array_combine($sub_type_array,$sub_type_array));

			$calling_status_array = explode(",",$config_m['calling_status']);
			$calling_status_field = $form->addField('dropdown','calling_status')->setEmptyText('Please Select');
			$calling_status_field->setValueList(array_combine($calling_status_array,$calling_status_array));

			$sub_type_3_array = explode(",",$config_m['sub_type_3']);
			$sub_type_3_field = $form->addField('DropDown','sub_type_3')->setEmptyText('Please Select');
			$sub_type_3_field->setValueList(array_combine($sub_type_3_array,$sub_type_3_array));

			$status_field = $form->addField('dropdown','call_direction');
			$status_field->setValueList(['Called'=>'Called (Out)','Received'=>'Received (In)'])->setEmptyText('Please Select');

			$email_to_field = $form->addField('email_to');
			$cc_email_field = $form->addField('cc_mails');
			$bcc_email_field = $form->addField('bcc_mails');

			$form->addField('title');
			$form->addField('xepan\base\RichText','body');

			$from_email=$form->addField('dropdown','from_email')->setEmptyText('Please Select From Email');
			$my_email = $form->add('xepan\hr\Model_Post_Email_MyEmails');
			$from_email->setModel($my_email);

			// $form->addField('line','from_phone');

			$from_number_field = $form->addField('xepan\base\DropDown','from_phone');
			$this->employee_phones = $this->app->employee->getPhones();
			$emp_phones = $this->employee_phones;
			$emp_phones = array_combine($emp_phones, $emp_phones);

			$company_m = $this->add('xepan\base\Model_Config_CompanyInfo');			
			$company_m->tryLoadAny();
			$company_number = explode(",", $company_m['mobile_no']);
			$company_number = array_combine($company_number, $company_number);

			$from_number_field->setValueList(array_filter($company_number)+array_filter($emp_phones));
			$from_number_field->select_menu_options = ['tags'=>true];
			$from_number_field->validate_values = false;

			$emp_field = $form->addField('DropDown','from_person');
			$emp_model = $this->add('xepan\hr\Model_Employee');			
			$emp_field->setModel($emp_model);
			$emp_field->set($this->app->employee->id);

			$called_to_field = $form->addField('xepan\base\DropDown','called_to');
			$called_to_field->select_menu_options=['tags'=>true];
			$called_to_field->validate_values=false;
			// $called_to_field->setAttr(['multiple'=>'multiple']);
			$form->addField('line','from_number');
			$form->addField('line','sms_to');
			$form->addField('DropDown','sms_settings')->setModel('xepan\communication\Model_Communication_SMSSetting');

			$follow_up_field = $form->addField('checkbox','follow_up','Add Followup');
			$starting_date_field = $form->addField('DateTimePicker','starting_at');
			$starting_date_field->js(true)->val('');
			$assign_to_field = $form->addField('DropDown','followup_assign_to');
			$assign_to_field->setModel('xepan\hr\Model_Employee')->addCondition('status','Active');
			$assign_to_field->set($this->app->employee->id);
			$description_field = $form->addField('text','description');
			
			$followup_type = $form->addField('DropDown','followup_type')->setValueList($this->task_subtype)->setEmptyText('Please Select ...');

			$set_reminder_field = $form->addField('checkbox','set_reminder');
			$remind_via_field = $form->addField('DropDown','remind_via')->setValueList(['Email'=>'Email','SMS'=>'SMS','Notification'=>'Notification'])->setAttr(['multiple'=>'multiple'])->setEmptyText('Please Select A Value');
			$notify_to_field = $form->addField('DropDown','notify_to')->setAttr(['multiple'=>'multiple'])->setEmptyText('Please select a value');
			$notify_to_field->setModel('xepan\hr\Model_Employee')->addCondition('status','Active');
			$reminder_time  = $form->addField('DateTimePicker','reminder_time');
			$reminder_time->js(true)->val('');

			// $force_remind_field = $form->addField('checkbox','force_remind','Enable Snoozing [Repetitive Reminder]');
			$snooze_field = $form->addField('snooze_duration');
			$remind_unit_field = $form->addField('DropDown','remind_unit')->setValueList(['Minutes'=>'Minutes','hours'=>'Hours','day'=>'Days'])->setEmptyText('Please select a value');
			
			$form->layout->add('xepan\projects\View_EmployeeFollowupSchedule',['employee_field'=>$assign_to_field,'date_field'=>$starting_date_field,'follow_type_field'=>$followup_type],'existing_schedule');

			$set_reminder_field->js(true)->univ()->bindConditionalShow([
				true=>['remind_via','notify_to','reminder_time','force_remind','snooze_duration','remind_unit']
			],'div.col-md-1,div.col-md-2,div.col-md-3,div.col-md-4,div.col-md-6,div.col-md-12');

			$emails_field->js('change',$email_to_field->js()->val($emails_field->js()->val()));
			$number_field->js('change','
				$("#'.$called_to_field->name.'").html("");
				$.each($("#'.$number_field->name.'").val().split(","), function(index,item){
					// console.log($("#'.$number_field->name.'").val());
					$("#'.$called_to_field->name.'").append($("<option/>", {
				        value: item, text: item
				    }));
				});
				$("#'.$called_to_field->name.'").trigger("change");
			');
			// $.each($('.$called_to_field.').val().split(","),function(item,index){$.create("option", {"value": '"+item+"'}, "").appendTo('#mySelect');})');

			$follow_up_field->js(true)->univ()->bindConditionalShow([
				true=>['follow_up_type','task_title','starting_at','followup_assign_to','description','set_reminder','followup_type','existing_schedule']
			],'div.col-md-1,div.col-md-2,div.col-md-3,div.col-md-4,div.col-md-6,div.col-md-12');

			$type_field->js(true)->univ()->bindConditionalShow([
				''=>[],
				'Email'=>['sub_type','calling_status','sub_type_3','email_to','cc_mails','bcc_mails','title','body','from_email','email_to','cc_mails','bcc_mails'],
				'Call'=>['sub_type','calling_status','sub_type_3','title','body','from_phone','from_person','called_to','notify_email','notify_email_to','status','calling_status','call_direction'],
				'TeleMarketing'=>['sub_type','calling_status','sub_type_3','title','body','from_phone','called_to'],
				'Personal'=>['sub_type','calling_status','sub_type_3','title','body','from_person'],
				'Comment'=>['sub_type','calling_status','sub_type_3','title','body','from_person'],
				'SMS'=>['sub_type','calling_status','sub_type_3','title','body','from_number','sms_to','sms_settings']
			],'div.col-md-1,div.col-md-2,div.col-md-3,div.col-md-4,div.col-md-6,div.col-md-12');

			$crud->addHook('formSubmit',function($crud,$form)use($model){

				// Manage communications
				if($form['communication_type']){
					
					if(!$form['title']){
						if(!$form['sub_type'] && !$form['calling_status']){
							$form->displayError('sub_type','Sub type, Calling Status or Title must be filled');
						}
						$form['title'] = $form['sub_type']. ' - ' . $form['calling_status'];
					}

					if(!$form['body']) $form->displayError('body','Please specify content');
					switch ($form['communication_type']) {
						case 'Email':
							if(!$form['title']) $form->displayError('title','Please specify title');
							if(!$form['email_to']) $form->displayError('email_to','Please specify "Email To" Value');
							foreach (explode(",", $form['email_to']) as $e) {
								if (!filter_var(trim($e), FILTER_VALIDATE_EMAIL)) {
									$form->displayError('email_to',$e.' is not an valid Email');
								}
							}
							if($form['cc_mails']){
								foreach (explode(",", $form['cc_mails']) as $e) {
									if (!filter_var(trim($e), FILTER_VALIDATE_EMAIL)) {
										$form->displayError('cc_mails',$e.' is not an valid Email');
									}
								}	
							}
							if($form['bcc_mails']){
								foreach (explode(",", $form['bcc_mails']) as $e) {
									if (!filter_var(trim($e), FILTER_VALIDATE_EMAIL)) {
										$form->displayError('bcc_mails',$e.' is not an valid Email');
									}
								}	
							}
							if(!$form['from_email']) $form->displayError('from_email','Please specify "From Email" value');
								break;
						case "Call":
							if(!$form['call_direction']) $form->displayError('call_direction','Please specify "Call Direction"');
							if(!$form['from_phone']) $form->displayError('from_phone','From Phone must not be empty');
							if(!$form['called_to']) $form->displayError('called_to','Called to  must not be empty');
							break;
						case "SMS":
						if(!$form['sms_to']) $form->displayError('sms_to','Please specify "sms_to" value');
						if(!$form['sms_settings']) $form->displayError('sms_settings','Please specify "sms_settings"');
							break;
						default:
							# code...
							break;
					}


					// if communication type is defined
					$model->addHook('afterSave',function($m)use($form){							
						// Actual communication save
						$this->processCommunicationSave($m,$form);
					});
				}

				// to handle score without communication
				$model->addHook('afterSave',function($m)use($form){							
					// INSERTING SCORE
					if($form['score']){
						$model_point_system = $this->add('xepan\base\Model_PointSystem');
						$model_point_system['contact_id'] = $m->id;
						$model_point_system['score'] = $form['score'];
						$model_point_system->save();
					}	

					// Followup 
					if($form['follow_up']){

						$model_task = $this->add('xepan\projects\Model_Task');
						$model_task['type'] = 'Followup';
						$model_task['task_name'] = 'Followup '. $m['name_with_type'];
						$model_task['created_by_id'] = $this->app->employee->id;
						$model_task['starting_date'] = $form['starting_at'];
						$model_task['assign_to_id'] = $form['followup_assign_to'];
						$model_task['description'] = $form['description'];
						$model_task['related_id'] = $m->id;
						$model_task['sub_type'] = $form['followup_type'];
						
						if($form['set_reminder']){
							$model_task['set_reminder'] = true;
							$model_task['reminder_time'] = $form['reminder_time'];
							$model_task['remind_via'] = $form['remind_via'];
							$model_task['notify_to'] = $form['notify_to'];
							
							if($form['force_remind']){
								$model_task['snooze_duration'] = $form['snooze_duration'];
								$model_task['remind_unit'] = $form['remind_unit'];

							}
						}
						$model_task->save();
					}

				});

			});
		}

		if($crud->isEditing()){ // add or edit to save contact details
			$model->addHook('afterSave',function($m)use($crud, $other_fields){
				$m->ref('Emails')->deleteAll();
				$m->ref('Phones')->deleteAll();
				
				foreach (explode(",",$crud->form['emails']) as $email) {
					if(!$email) continue;
					$m->addEmail(trim($email),'Official',null,null,null,false);
				}

				foreach (explode(",",$crud->form['numbers']) as $no) {
					if(!$no) continue;
					$m->addPhone(trim($no),'Official',null,null,null,false);
				}

				$cats_from_field = is_array($crud->form['categories'])?$crud->form['categories']:explode(",",$crud->form['categories']);
				// var_dump($cats_from_field);
				$cat_diff = array_diff($m->getAssociatedCategories(),$cats_from_field);
				foreach ($cats_from_field as $cat) {
					if(!$cat) continue;
					$m->associateCategory($cat);
				}

				foreach ($cat_diff as $cat) {
					$this->add('xepan\marketing\Model_Lead_Category_Association')
						->addCondition('lead_id',$m->id)
		     			->addCondition('marketing_category_id',$cat)
						->tryLoadAny()->tryDelete();
				}

				$i=1;

				foreach ($other_fields as $of_norm => $of_name) {
					if(!$of_name) continue;

					$this->add('xepan\base\Model_Contact_Other')
						->addCondition('contact_id',$m->id)
						->addCondition('head',$of_name)
						->tryLoadAny()
						->set('value',$crud->form[$of_norm])
						->set('is_active',1)
						->set('is_valid',1)
						->save();
				}

			});
			$i=1;

			$contact_other_info_config_m = $this->add('xepan\base\Model_Config_ContactOtherInfo');
			$contact_other_info_config_m->addCondition([['for','Lead'],['for','Contact']]);

			foreach ($contact_other_info_config_m->config_data as $of) {
				if($of['for'] != "Contact" && $of['for'] != "Lead" ) continue;

				if(!$of['name']) continue;

				$form = $crud->form;
				$field_name = $this->app->normalizeName($of['name']);

				$field = $form->addField($of['type'],$field_name,$of['name']);
				if($of['type']=='DropDown') $field->setValueList(array_combine(explode(",", $of['possible_values']), explode(",", $of['possible_values'])))->setEmptyText('Please Select');

				$has_field = true;

				if($crud->isEditing('edit')){
					$existing = $this->add('xepan\base\Model_Contact_Other')
						->addCondition('contact_id',$model->id)
						->addCondition('head',$of['name'])
						->tryLoadAny();
					$field->set($existing['value']);
				}

				if($of['conditional_binding']){
					$field->js(true)->univ()->bindConditionalShow(json_decode($of['conditional_binding'],true),'div.flc-atk-form-row');
				}

				if($of['is_mandatory']){
					$field->validate('required');
				}

			}
		}

	}

	function processCommunicationSave($m,$form){
		$commtype = $form['communication_type'];
		$this->contact = $m;
					
		$communication = $this->add('xepan\communication\Model_Communication_'.$commtype);

		$communication['from_id']=$form['from_person'];
		$communication['to_id']= $this->contact->id;
		$communication['sub_type']=$form['sub_type'];
		$communication['calling_status']=$form['calling_status'];
		$communication['sub_type_3']=$form['sub_type_3'];
		$communication['score']=$form['score'];
		

		switch ($commtype) {
			case 'Email':
				$send_settings = $form->add('xepan\communication\Model_Communication_EmailSetting');
				$send_settings->tryLoad($form['from_email']?:-1);
				$_from = $send_settings['from_email'];
				$_from_name = $send_settings['from_name'];
				$_to_field='email_to';
				$communication['from_id']=$this->app->employee->id;
				$communication->setFrom($_from,$_from_name);
				$communication['direction']='Out';
				break;
			case 'TeleMarketing':
				$communication['from_id']=$form['from_person'];
				$communication['status'] = 'Called';	
				$_to_field='called_to';
			case 'Call':
				$send_settings = $form['from_phone'];
				if($form['status']=='Received'){
					$communication['from_id']=$this->contact->id;
					$communication['to_id']=$form['from_person']; // actually this is to person this time
					$communication['direction']='In';
					$communication->setFrom($form['from_phone'],$this->contact['name']);
				}else{					
					$communication['from_id']=$form['from_person']; // actually this is to person this time
					$communication['to_id']=$this->contact->id;
					$communication['direction']='Out';
					$employee_name=$this->add('xepan\hr\Model_Employee')->load($form['from_person'])->get('name');
					$communication->setFrom($form['from_phone'],$employee_name);
				}


				$communication['status']=$form['status'];
				$_to_field='called_to';

				break;

			case 'SMS':
				
				$send_settings = $this->add('xepan\communication\Model_Epan_SMSSetting');
				$send_settings->load($form['from_sms']);
				$communication['from_id'] = $this->app->employee->id;
				$communication['description'] = $form['body'];
				$_from = $this->app->employee->id;
				$_from_name = $this->app->employee['name'];
				$_to_field='sms_to';
				foreach (explode(",", $form[$_to_field]) as $nos) {
					$communication->addTo($nos,$this->contact['name']);
					
				}
				$communication->setFrom($_from,$_from_name);
				$communication['direction']='Out';
				$communication['communication_channel_id'] = $form['from_sms'];
				$communication['title'] = 'SMS: '.substr(strip_tags($thformis['body']),0,35)." ...";
				break;
			case 'Personal':
				$_from = $form['from_person'];
				$_from_name = $this->add('xepan\base\Model_Contact')->load($_from)->get('name');
				$_to = $this->contact->id;
				$_to_name = $this->contact['name'];
				$_to_field=null;
				$communication->addTo($_to, $_to_name);
				$communication->setFrom($_from,$_from_name);
				break;
			case 'Comment':
				$_from = $form['from_person'];
				$_from_name = $this->add('xepan\base\Model_Contact')->load($_from)->get('name');
				$_to = $this->contact->id;
				$_to_name = $this->contact['name'];
				$_to_field=null;
				$communication->addTo($_to, $_to_name);
				$communication->setFrom($_from,$_from_name);
				break;	
			default:
				break;
		}
		
		$communication->setSubject($form['title']);
		$communication->setBody($form['body']);

		if($_to_field){
			foreach (explode(',',$form[$_to_field]) as $to) {
				$communication->addTo(trim($to));
			}			
		}
		
		if($form['bcc_mails']){
			foreach (explode(',',$form['bcc_mails']) as $bcc) {
					if( ! filter_var(trim($bcc), FILTER_VALIDATE_EMAIL))
						$form->displayError('bcc_mails',$bcc.' is not a valid email');
				$communication->addBcc($bcc);
			}
		}

		if($form['cc_mails']){
			foreach (explode(',',$form['cc_mails']) as $cc) {
					if( ! filter_var(trim($cc), FILTER_VALIDATE_EMAIL))
						$form->displayError('cc_mails',$cc.' is not a valid email');
				$communication->addCc($cc);
			}
		}

		if($form->hasElement('date')){
			$communication['created_at'] = $form['date'];
		}

		if(isset($send_settings)){
					
			$communication->send($send_settings);			
		}else{
			$communication['direction']='Out';
			$communication->save();
		}
	}

	function page_import(){
		
		$form = $this->add('Form');
		$form->addSubmit('Download Sample File');
		
		if($_GET['download_sample_csv_file']){
			$output = ['first_name','last_name','address','city','state','country','pin_code','organization','post','website','source','remark','personal_email_1','personal_email_2','official_email_1','official_email_2','personal_contact_1','personal_contact_2','official_contact_1','official_contact_2','category'];

			$output = implode(",", $output);
	    	header("Content-type: text/csv");
	        header("Content-disposition: attachment; filename=\"sample_xepan_lead_import.csv\"");
	        header("Content-Length: " . strlen($output));
	        header("Content-Transfer-Encoding: binary");
	        print $output;
	        exit;
		}

		if($form->isSubmitted()){
			$form->js()->univ()->newWindow($form->app->url('xepan_marketing_lead_import',['download_sample_csv_file'=>true]))->execute();
		}

		$this->add('View')->setElement('iframe')->setAttr('src',$this->api->url('./execute',array('cut_page'=>1)))->setAttr('width','100%');
	}
	
	function downloadsamplefile(){

	}

	function page_import_execute(){

		ini_set('max_execution_time', 0);

		$form= $this->add('Form');
		$form->template->loadTemplateFromString("<form method='POST' action='".$this->api->url(null,array('cut_page'=>1))."' enctype='multipart/form-data'>
			<input type='file' name='csv_lead_file'/>
			<input type='submit' value='Upload'/>
			</form>"
			);

		if($_FILES['csv_lead_file']){
			if ( $_FILES["csv_lead_file"]["error"] > 0 ) {
				$this->add( 'View_Error' )->set( "Error: " . $_FILES["csv_lead_file"]["error"] );
			}else{
				$mimes = ['text/comma-separated-values', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.ms-excel', 'application/vnd.msexcel', 'text/anytext'];
				if(!in_array($_FILES['csv_lead_file']['type'],$mimes)){
					$this->add('View_Error')->set('Only CSV Files allowed');
					return;
				}

				$importer = new \xepan\base\CSVImporter($_FILES['csv_lead_file']['tmp_name'],true,',');
				$data = $importer->get();

				$lead = $this->add('xepan\marketing\Model_Lead');
				$lead->addLeadFromCSV($data);
				$this->add('View_Info')->set('Total Records : '.count($data));
			}
		}
	}

	function page_grab(){
		$extra_info = $this->app->recall('epan_extra_info_array',false);

		if($extra_info ['specification']['Data Grabber'] != "Yes"){
			$this->add('View')->addClass('alert alert-danger')->set('You are not permitted to use this services');
			return;
		}

		set_time_limit(0);
		// echo "TYpe of code : Google serach result page, website, portal, yahoo search result page, bing search result page <br/>";
		// echo "Selector for urls <br/>";
		// echo "Code Block :: text area <br/>";
		$array	=
				[
					''=>'Please Select',
					'h3 a'=>'Google Search Result Page',
					// 'Website Page'=>'Website Page',
					// 'Yahoo Search Result Page'=>'Yahoo Search Result Page',
					// 'Portal'=>'Portal',
					// 'Other'=>'Other'
				];
		$pages_selector = $this->app->stickyGET('type_of_pages');		
		$f=$this->add('Form');
		$tyop = $f->addField('DropDown','type_of_pages')->setValueList($array);
		$url_select = $f->addField('line','url_selector')->set($pages_selector)->validate('required');
		$f->addField('text','html_code');
		$category_field = $f->addField('Dropdown','categories');
		$category_field->setModel('xepan\marketing\Model_MarketingCategory');
		$category_field->setAttr(['multiple'=>'multiple']);

		$f->addSubmit('Grab');
		// $tyop->js('change',$url_select->js()->reload(null,null,[$this->app->url(null,['cut_object'=>$url_select->name]),'type_of_pages'=>$tyop->js()->val()]));
		$tyop->js('change',$f->js()->atk4_form('reloadField','url_selector',[$this->app->url(null,['cut_object'=>$url_select->name]),'type_of_pages'=>$tyop->js()->val()]));
		// $tyop->js('change',$url_select->js()->reload(['type_of_pages'=>$tyop->js()->val()]));
	
		if($f->isSubmitted()){
			$category = $f['categories'];
			$category_array = explode(",", $category);			

			$this->grab('http://searchpage.null/root',$f['html_code'],$f['url_selector']);
			$unique_emails=[];
			foreach ($this->grabbed_data as $host => $pages) {
				
				if(!isset($unique_emails[$host])){
					$unique_emails[$host]=[];
				}

				foreach ($pages as $page => $emails) {
					foreach ($emails as $email) {
						if(!in_array($email, $unique_emails[$host]))
							$unique_emails[$host][] = $email;
					}
				}
			}

			$this->api->db->beginTransaction();
			try {

				// get all emails and find existing leads first here
				// get all existing contact emails with their lead id
				$all_emails = [];
				$this->insert_sql =[];

				foreach ($unique_emails as $host => $emails) {
					$all_emails = array_merge($all_emails,$emails);
				}

				// echo "So all emails to be check are as follows <br/>";
				// var_dump($all_emails);

				if(count($all_emails)){
					$existing_email=$this->add('xepan\base\Model_Contact_Email');
					$existing_email->addCondition('value',$all_emails);
					$existing_lead_data = $existing_email->getRows();

					$already_in_database_emails = [];
					foreach ($existing_lead_data as $dt) {
						$contact_id = $dt['contact_id'];
						$already_in_database_emails [] = $dt['value'];
						foreach ($category_array as $cat_id) {
							$this->insert_sql [] = "INSERT IGNORE INTO lead_category_association (id, lead_id, marketing_category_id, created_at) VALUES (0,$contact_id,$cat_id,'".$this->app->now."'); ";
						}
					}
				}


				// echo "already in database emails <br/>";
				// var_dump($already_in_database_emails);

				foreach ($unique_emails as $host => $emails) {
					foreach ($emails as $em) {
						if(!in_array($em, $already_in_database_emails)) {
							// echo "creating lead for $em <br/>";
							$this->createLead($em,$host, $category_array);
						}
					}

				}

				// echo "<br/>".implode("<br/>", $this->insert_sql) ."<br/>";
				$this->app->db->dsql()->expr(implode("", $this->insert_sql))->execute();
				$this->api->db->commit();
	        }catch(Exception $e){
	            $this->api->db->rollback();
	            throw $e;
	        }

			$js=[
					$f->js()->closest('.dialog')->dialog('close'),
					$this->js()->_selector('.grab-lead-grid')->trigger('reload')
				];

			$f->js(null,$js)->univ()->successMessage('Leads Grabbed')->execute();
		}

	}

	function grab($url, $content, $regex_selector /*, $max_page_depth, $max_domain_depth, $total_max_page_depth, $initial_domain_depth, $path*/){
		
		try{
		
			$parsed_url = parse_url($url);

			$start=microtime(true);
			// get Emails and Mobile Number and ... 
			$pattern = '/[a-z0-9_\-\+\.]+(@|(.)?\[(.)?at(.)?\](.)?)[a-z0-9\-]+(\.|(.)?\[(.)?dot(.)?\](.)?)([a-z]{2,3})(?:(\.|(.)?\[(.)?dot(.)?\](.)?)[a-z]{2})?/i';
			$pattern = '/[a-z0-9_\-\+\.]{1,80}+@[a-z0-9\-]{1,80}+\.([a-z]{2,3})(?:\.[a-z]{2})?/i';
			// preg_match_all returns an associative array
			preg_match_all($pattern, $content, $email_found);
			// echo '<br/>'.$path . " [<b> $url </b>] @ <b>$max_page_depth</b> level". "<br/>";
			$end=microtime(true);
			// echo print_r($email_found[0],true) . ' in '.($end-$start).' seconds from <b>'.$url.'</b><br/>';
			// ob_flush();
			// flush();

			$this->grabbed_data[$parsed_url['host']][$parsed_url['path'] . $parsed_url['query']] = $email_found[0];

			$pq = new phpQuery();
			$doc = @$pq->newDocumentHTML($content);
			
			// if($max_domain_depth== $initial_domain_depth)
				$get_a = $doc[$regex_selector];
			// else
				// $get_a = $doc['a:contains("contact")'];

			// echo "Found Links: ";
			
			$unique_filtered_links = array();

			foreach ($get_a as $a) {
				// echo '<br/>--------  &nbsp; &nbsp; &nbsp; '.$pq->pq($a)->attr('href'). ' <br/>';
				preg_match('/(\.pdf|\.exe|\.msi|\.zip|\.rar|\.gz|\.tar|\.flv|\.mov|\.mpg|\.mpeg)/i', $pq->pq($a)->attr('href'),$arr);
				if(count($arr)) {
					// echo "Found pdf etc so not taking to check in ". $pq->pq($a)->attr('href') .'<br/>';
					continue;
				}


				$new_website = parse_url($pq->pq($a)->attr('href'));
				if(!$new_website['scheme']) $new_website['scheme'] = $parsed_url['scheme'];
				if(!$new_website['host']) $new_website['host'] = $parsed_url['host'];
				$new_url = $new_website['scheme'].'://'.$new_website['host'] . '/'.$new_website['path'].$new_website['query'];

				// if(in_array($new_website['path'].$new_website['query'], array_keys($this->grabbed_data[$parsed_url['host']]))){
				// 	echo "Already Visited <br/>";
				// 	continue;
				// }

				if(!in_array($new_url, $unique_filtered_links)){
					$unique_filtered_links[] = $new_url;
				}
			}
			// echo "Unique Links to check <br/>";
			// print_r($unique_filtered_links);

			$start = microtime(true);
			$results = $this->multi_request($unique_filtered_links);
			// ==================== 
			// echo "Fetched ". count($unique_filtered_links).  " websites in ". (microtime(true) - $start) . ' seconds <br/>';

			$contact_us_pages =array();
			foreach ($unique_filtered_links as $id => $site_url) {
				// somehow if no result was found just carry on
				if(!$results[$id]) {
					// echo "No Result for " . $site_url. '<br/>';
					continue;
				}

				$parsed_url = parse_url($site_url);

				preg_match_all($pattern, $results[$id], $email_found);
				$this->grabbed_data[$parsed_url['host']][$parsed_url['path'] . $parsed_url['query']] = $email_found[0];

				
				$doc = @$pq->newDocumentHTML($results[$id]);
				$get_a = $doc['a:contains("contact")'];

				foreach ($get_a as $a) {
					// echo '<br/>--------  &nbsp; &nbsp; &nbsp; '.$pq->pq($a)->attr('href'). ' <br/>';
					preg_match('/(\.pdf|\.exe|\.msi|\.zip|\.rar|\.gz|\.tar|\.flv|\.mov|\.mpg|\.mpeg)/i', $pq->pq($a)->attr('href'),$arr);
					if(count($arr)) {
						// echo "Found pdf etc so not taking to check in ". $pq->pq($a)->attr('href') .'<br/>';
						continue;
					}


					$new_website = parse_url($pq->pq($a)->attr('href'));
					if(!$new_website['scheme']) $new_website['scheme'] = $parsed_url['scheme'];
					if(!$new_website['host']) $new_website['host'] = $parsed_url['host'];
					$new_url = $new_website['scheme'].'://'.$new_website['host'] . '/'.$new_website['path'].$new_website['query'];

					// if(in_array($new_website['path'].$new_website['query'], array_keys(is_array($this->grabbed_data[$parsed_url['host']])?:array()))){
					// 	echo "Already Visited <br/>";
					// 	continue;
					// }

					if(!in_array($new_url, $contact_us_pages)){
						$contact_us_pages[] = $new_url;
					}
				}
			}
			
			// echo "Unique Contact Links to check <br/>";
			// print_r($contact_us_pages);

			$start = microtime(true);
			$results = $this->multi_request($contact_us_pages);
			
			// ====================
			// echo "Fetched ". count($contact_us_pages).  " contact-pages in ". (microtime(true) - $start) . ' seconds <br/>';

			foreach ($results as $id => $contact_page_content) {
				if(!$results[$id]){
					// echo "Contact Page no result ". $contact_us_pages[$id] .'<br/>';
					continue;
				}

				$parsed_url = parse_url($contact_us_pages[$id]);

				preg_match_all($pattern, $contact_page_content, $email_found);
				$this->grabbed_data[$parsed_url['host']][$parsed_url['path'] . $parsed_url['query']] = $email_found[0];
			}

		}catch(Exception $e){
			return;
		}
	}

	function multi_request($urls)
	{
		$curly = array();
		$result = array();
		$mh = curl_multi_init();

		foreach ($urls as $id => $url) {
			$curly[$id] = curl_init();
			curl_setopt($curly[$id], CURLOPT_URL, $url);
			curl_setopt($curly[$id], CURLOPT_HEADER, 0);
			curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curly[$id], CURLOPT_TIMEOUT, 30);
			curl_setopt($curly[$id], CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curly[$id], CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curly[$id], CURLOPT_SSL_VERIFYHOST, 0);
			curl_multi_add_handle($mh, $curly[$id]);
		}

		$running = null;
		do {
			curl_multi_exec($mh, $running);
		} while($running > 0);

		foreach($curly as $id => $c) {
			$result[$id] = curl_multi_getcontent($c);
			curl_multi_remove_handle($mh, $c);
		}
		curl_multi_close($mh);
		return $result;
	}


	function createLead($email,$url, $category_array){

		$company_info = $this->app->epan['name'];
		$owner_code = substr($company_info, 0,3);
		$code = $owner_code.'LEA';

		$email_parts = explode("@", $email);
		$first_name = $email_parts[0];
		unset($email_parts[0]);
		$last_name = "@" . implode("", $email_parts);
		$website = $url;
		$source = 'Data Grabber';

		$search_string = $first_name." ".$last_name." ".$website." ".$source." ".$email;
		$this->insert_sql [] = "INSERT INTO contact (id, first_name, last_name, type, website, source, created_at, updated_at,created_by_id,score,freelancer_type,search_string, status) VALUES (0,'$first_name','$last_name','Contact','$website','$source','".$this->app->now."','".$this->app->now."','".$this->app->employee->id."',0,'Not Applicable','$search_string','Active'); SET @last_lead_id = LAST_INSERT_ID();";
		$this->insert_sql [] = "UPDATE contact set code = concat('$code',@last_lead_id) WHERE id = @last_lead_id;";
		$this->insert_sql [] = "INSERT INTO contact_info (id, contact_id, head, value, is_active, is_valid, type) VALUES (0,@last_lead_id,'Official','$email',1,1,'Email');";
		foreach ($category_array as $cat_id) {
			$this->insert_sql[] = "INSERT IGNORE INTO lead_category_association (id, lead_id, marketing_category_id, created_at) VALUES (0,@last_lead_id,$cat_id,'".$this->app->now."'); ";
		}

	}
}