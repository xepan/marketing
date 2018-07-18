<?php

namespace xepan\marketing;  

class Model_Lead extends \xepan\base\Model_Contact{

	public $status = ['Active','InActive'];
	public $actions = [
					'Active'=>['view','edit','delete','deactivate','communication','send','create_opportunity','manage_score'],
					'InActive'=>['view','edit','delete','activate','communication','manage_score']
					];

	public $assigable_by_field = 'assign_to_id';
	public $contact_type = "Contact";
	
	function init(){
		parent::init();
		
		$config_m = $this->add('xepan\marketing\Model_Config_LeadSource');
		$config_m->tryLoadAny();

		$this->getElement('source')->enum(explode(',', $config_m['lead_source']));

		$this->getElement('created_by_id')->defaultValue($this->app->employee->id);

		$this->addExpression('open_count')->set(function($m,$q){
			return $this->add('xepan\marketing\Model_Opportunity',['table_alias'=>'open_count'])
						->addCondition('lead_id',$q->getField('id'))
						->addCondition('status','Open')
						->count();
		});

		$this->addExpression('converted_count')->set(function($m,$q){
			return $this->add('xepan\marketing\Model_Opportunity',['table_alias'=>'converted_count'])
						->addCondition('lead_id',$q->getField('id'))
						->addCondition('status','Converted')
						->count();
		});

		$this->addExpression('rejected_count')->set(function($m,$q){
			return $this->add('xepan\marketing\Model_Opportunity',['table_alias'=>'rejected_count'])
						->addCondition('lead_id',$q->getField('id'))
						->addCondition('status','Rejected')
						->count();
		});

		$this->addExpression('total_visitor')->set(function($m,$q){
			return $this->add('xepan\marketing\Model_LandingResponse')
					->addCondition('contact_id',$q->getField('id'))
					->count();
		});

		/********************************************
			PRIORITY EXPRESSIONS START
		*********************************************/
		$this->addExpression('last_communication')->set(function($m,$q){
			$last_commu = $m->add('xepan\communication\Model_Communication');
			$last_commu->addCondition(
							$last_commu->dsql()->orExpr()
								->where('from_id',$q->getField('id'))
								->where('to_id',$q->getField('id'))
							)
						->setOrder('id','desc')
						->setLimit(1);
			return $q->expr('DATE_FORMAT([0],"%M %d, %Y")',[$last_commu->fieldQuery('created_at')]);
		});


		$this->addExpression('last_landing_response_date_from_lead')->set(function($m,$q){
			$landing_response = $m->add('xepan\marketing\Model_LandingResponse')
									->addCondition('contact_id',$m->getElement('id'))
									->setLimit(1)
									->setOrder('date','desc');
			return $q->expr("[0]",[$landing_response->fieldQuery('date')]);
		});

		$this->addExpression('last_communication_date_from_lead')->set(function($m,$q){
			$communication = $m->add('xepan\communication\Model_Communication')->addCondition('from_id',$m->getElement('id'))->addCondition('direction','In')->setLimit(1)->setOrder('created_at','desc');
			return $q->expr("[0]",[$communication->fieldQuery('created_at')]);
		});


		$this->addExpression('last_communication_date_from_company')->set(function($m,$q){
			$communication = $m->add('xepan\communication\Model_Communication')->addCondition('to_id',$m->getElement('id'))->addCondition('direction','Out')->setLimit(1)->setOrder('created_at','desc');
			return $q->expr("[0]",[$communication->fieldQuery('created_at')]);
		});

		// current date - max from last_landing_from_lead, last_communication_form_lead or last_communication_form_employee
		$this->addExpression('days_ago')->set(function($m,$q){
			return $q->expr("DATEDIFF([0], IFNULL(GREATEST([1],COALESCE([2],0),COALESCE([3],0)),[0]))",
								[
									'"'.$this->app->now.'"',
									$m->getElement('last_landing_response_date_from_lead'),
									$m->getElement('last_communication_date_from_lead'),
									$m->getElement('last_communication_date_from_company')
								]
						);
		});

		// return days ago * score * k .// here k is constant
		$k = 1;
		$this->addExpression('priority')->set(function($m,$q)use($k){
			return $q->expr('[0] * [1] * [2]',[$m->getElement('last_communication_before_days'),$m->getElement('score'),$k]);
		})->sortable(true);
		
		/********************************************
			PRIORITY EXPRESSIONS STOP
		*********************************************/

		$this->hasMany('xepan\marketing\Opportunity','lead_id',null,'Opportunities');
		$this->hasMany('xepan\marketing\Lead_Category_Association','lead_id');
		
		$this->getElement('status')->defaultValue('Active');
		$this->addHook('beforeDelete',[$this,'checkContactIsLead']);
		$this->addHook('beforeDelete',[$this,'checkExistingOpportunities']);
		$this->addHook('beforeDelete',[$this,'checkExistingCategoryAssociation']);
		$this->addHook('beforeDelete',[$this,'deleteUnsubscribe']);

		$this->addHook('beforeSave',[$this,'updateSearchString']);

	}

	function deleteUnsubscribe(){
		$this->add('xepan\marketing\Model_Unsubscribe')->addCondition('contact_id',$this->id)->deleteAll();
	}

	function updateSearchString($m){
		$search_string = ' ';
		$search_string .=" ". $this['name'];
		$search_string .=" ". str_replace("<br/>", " ", $this['contacts_str']);
		$search_string .=" ". str_replace("<br/>", " ", $this['emails_str']);
		$search_string .=" ". $this['source'];
		$search_string .=" ". $this['type'];
		$search_string .=" ". $this['city'];
		$search_string .=" ". $this['state'];
		$search_string .=" ". $this['pin_code'];
		$search_string .=" ". $this['organization'];
		$search_string .=" ". $this['post'];
		$search_string .=" ". $this['website'];

		$this['search_string'] = $search_string;
	}

	function activityReport($app,$report_view,$emp,$start_date,$end_date){		
		$employee = $this->add('xepan\hr\Model_Employee')->load($emp);
							  					  
		$lead = $this->add('xepan\marketing\Model_Lead');
		$lead->addCondition('created_at','>=',$start_date);
		$lead->addCondition('created_at','<=',$this->app->nextDate($end_date));
		$lead->addCondition('created_by_id',$emp);
		$lead_count = $lead->count()->getOne();
		
		$result_array[] = [
 					'assign_to'=>$employee['name'],
 					'from_date'=>$start_date,
 					'to_date'=>$end_date,
 					'type'=> 'Lead',
 					'count'=>$lead_count,
 				];

		$opportunity = $this->add('xepan\marketing\Model_Opportunity');
		$opportunity->addCondition('created_at','>=',$start_date);
		$opportunity->addCondition('created_at','<=',$this->app->nextDate($end_date));
		$opportunity->addCondition('created_by_id',$emp);
		$opportunity_count = $opportunity->count()->getOne();
		
		$result_array[] = [
 					'assign_to'=>$employee['name'],
 					'from_date'=>$start_date,
 					'to_date'=>$end_date,
 					'type'=> 'Opportunity',
 					'count'=>$opportunity_count,
 				];

 		$newsletter = $this->add('xepan\marketing\Model_Newsletter');
		$newsletter->addCondition('created_at','>=',$start_date);
		$newsletter->addCondition('created_at','<=',$this->app->nextDate($end_date));
		$newsletter->addCondition('created_by_id',$emp);
		$newsletter_count = $newsletter->count()->getOne();
		
		$result_array[] = [
 					'assign_to'=>$employee['name'],
 					'from_date'=>$start_date,
 					'to_date'=>$end_date,
 					'type'=> 'Newsletter',
 					'count'=>$newsletter_count,
 				];

 		$socialpost = $this->add('xepan\marketing\Model_SocialPost');
		$socialpost->addCondition('created_at','>=',$start_date);
		$socialpost->addCondition('created_at','<=',$this->app->nextDate($end_date));
		$socialpost->addCondition('created_by_id',$emp);
		$socialpost_count = $socialpost->count()->getOne();
		
		$result_array[] = [
 					'assign_to'=>$employee['name'],
 					'from_date'=>$start_date,
 					'to_date'=>$end_date,
 					'type'=> 'Social Post',
 					'count'=>$socialpost_count,
 				];

		$sms = $this->add('xepan\marketing\Model_Sms');
		$sms->addCondition('created_at','>=',$start_date);
		$sms->addCondition('created_at','<=',$this->app->nextDate($end_date));
		$sms->addCondition('created_by_id',$emp);
		$sms_count = $sms->count()->getOne();
		
		$result_array[] = [
 					'assign_to'=>$employee['name'],
 					'from_date'=>$start_date,
 					'to_date'=>$end_date,
 					'type'=> 'SMS',
 					'count'=>$sms_count,
 				];		

 		$telecommunication = $this->add('xepan\marketing\Model_TeleCommunication');
		$telecommunication->addCondition('created_at','>=',$start_date);
		$telecommunication->addCondition('created_at','<=',$this->app->nextDate($end_date));
		$telecommunication->addCondition('created_by_id',$emp);
		$telecommunication_count = $telecommunication->count()->getOne();
		
		$result_array[] = [
 					'assign_to'=>$employee['name'],
 					'from_date'=>$start_date,
 					'to_date'=>$end_date,
 					'type'=> 'TeleCommunication',
 					'count'=>$telecommunication_count,
 				];		
		

		$cl = $report_view->add('CompleteLister',null,null,['view\marketingactivityreport']);
		$cl->setSource($result_array);		
	}

	function quickSearch($app,$search_string,&$result_array,$relevency_mode){
		$this->addExpression('Relevance')->set('MATCH(search_string) AGAINST ("'.$search_string.'" '.$relevency_mode.')');
		$this->addCondition('Relevance','>',0);
 		$this->setOrder('Relevance','Desc');
 		
 		if($this->count()->getOne()){
 			foreach ($this->getRows() as $data) { 				
 				$result_array[] = [
 					'image'=>null,
 					'title'=>$data['name'],
 					'relevency'=>$data['Relevance'],
 					'url'=>$this->app->url('xepan_marketing_leaddetails',['status'=>$data['status'],'contact_id'=>$data['id']])->getURL(),
 					'type_status'=>$data['type'].' '.'['.$data['status'].']',
 				];
 			}
		}


 		$opportunity = $this->add('xepan\marketing\Model_Opportunity');
		$opportunity->addExpression('Relevance')->set('MATCH(search_string) AGAINST ("'.$search_string.'" '.$relevency_mode.')');
		$opportunity->addCondition('Relevance','>',0);
 		$opportunity->setOrder('Relevance','Desc'); 		
 		
 		if($opportunity->count()->getOne()){
 			foreach ($opportunity->getRows() as $data) { 				
 				$result_array[] = [
 					'image'=>null,
 					'title'=>$data['title'],
 					'relevency'=>$data['Relevance'],
 					'url'=>$this->app->url('xepan_marketing_leaddetails',['status'=>$data['status'],'contact_id'=>$data['id']])->getURL(),
 					'type_status'=>$data['type'].' '.'['.$data['status'].']',
 				];
 			}
		}

 		$category = $this->add('xepan\marketing\Model_MarketingCategory');
		$category->addExpression('Relevance')->set('MATCH(search_string) AGAINST ("'.$search_string.'" '.$relevency_mode.')');
		$category->addCondition('Relevance','>',0);
 		$category->setOrder('Relevance','Desc');
 			
 		if($category->count()->getOne()){
 			foreach ($category->getRows() as $data) { 				
 				$result_array[] = [
 					'image'=>null,
 					'title'=>$data['name'],
 					'relevency'=>$data['Relevance'],
 					'url'=>$this->app->url('xepan_marketing_marketingcategory',['status'=>$data['status']])->getURL(),
 					'type_status'=>$data['type'].' '.'['.$data['status'].']',
 				];
 			}
		}

 		$content = $this->add('xepan\marketing\Model_Content');
		$content->addExpression('Relevance')->set('MATCH(search_string) AGAINST ("'.$search_string.'" '.$relevency_mode.')');
		$content->addCondition('Relevance','>',0);
 		$content->setOrder('Relevance','Desc');
 		
 		if($content->count()->getOne()){
 			foreach ($content->getRows() as $data) { 
 				if($data['type'] =='Newsletter')
     				$url = $this->app->url('xepan_marketing_newsletterdesign',['status'=>$data['status'],'document_id'=>$data['id']])->getURL();
     			if($data['type'] =='Sms');
     				$url = $this->app->url('xepan_marketing_addsms',['status'=>$data['status'],'document_id'=>$data['id']])->getURL();
     			if($data['type'] =='SocialPost');	
     				$url = $this->app->url('xepan_marketing_addsocialpost',['status'=>$data['status'],'document_id'=>$data['id']])->getURL();				
 				
 				$result_array[] = [
 					'image'=>null,
 					'title'=>$data['title'],
 					'relevency'=>$data['Relevance'],
 					'url'=>$url,
 					'type_status'=>$data['type'].' '.'['.$data['status'].']',
 				];
 			}
		}

 		$campaign = $this->add('xepan\marketing\Model_Campaign');
		$campaign->addExpression('Relevance')->set('MATCH(search_string) AGAINST ("'.$search_string.'" '.$relevency_mode.')');
		$campaign->addCondition('Relevance','>',0);
 		$campaign->setOrder('Relevance','Desc');
	 		
 		if($campaign->count()->getOne()){
 			foreach ($campaign->getRows() as $data) {
 			 				 				
 				$result_array[] = [
 					'image'=>null,
 					'title'=>$data['title'],
 					'relevency'=>$data['Relevance'],
 					'url'=>$this->app->url('xepan_marketing_campaign',['status'=>$data['status']])->getURL(),
 					'type_status'=>$data['type'].' '.'['.$data['status'].']',
 				];
 			}
		}
 		
 		$tele = $this->add('xepan\marketing\Model_TeleCommunication');
		$tele->addExpression('Relevance')->set('MATCH(title, description, communication_type) AGAINST ("'.$search_string.'" '.$relevency_mode.')');
		$tele->addCondition('Relevance','>',0);
 		$tele->setOrder('Relevance','Desc');
 		
 		if($tele->count()->getOne()){
 			foreach ($tele->getRows() as $data) { 				
 				$result_array[] = [
 					'image'=>null,
 					'title'=>$data['title'],
 					'relevency'=>$data['Relevance'],
 					'url'=>$this->app->url('xepan_marketing_telemarketing',['status'=>$data['status']])->getURL(),
 					'type_status'=>$data['type'].' '.'['.$data['status'].']',
 				];
 			}
		}
	}
	
	function page_create_opportunity($page){
		$crud = $page->add('xepan\hr\CRUD',null,null,['grid\miniopportunity-grid']);		
		$opportunity = $this->add('xepan\marketing\Model_Opportunity');
		$crud->grid->addQuickSearch(['title']);
		$opportunity->addCondition('lead_id',$this->id);
		$opportunity->setOrder('created_at','desc');
		$opportunity->getElement('assign_to_id')->getModel()->addCondition('type','Employee');
		
		$opportunity->addHook('afterInsert',function($m){
			$this->opportunityMessage();
		});

		$crud->setModel($opportunity,['title','description','status','assign_to_id','fund','discount_percentage','closing_date']);
	}

	function opportunityMessage(){
		$opportunity = $this->add('xepan\marketing\Model_Opportunity');
		$opportunity->addCondition('lead_id',$this->id);
		$opportunity->setOrder('id','desc');
		$opportunity->tryLoadAny();
		$this->app->employee
            ->addActivity("Opportunity : '".$opportunity['title']."' Created, Related To Lead : '".$this['name']."'", null/* Related Document ID*/, $this->id /*Related Contact ID*/,null,null,"xepan_marketing_leaddetails&contact_id=".$this->id."")
            ->notifyWhoCan('create_opportunity','Active',$this);
	}

	function activate(){
		$this['status']='Active';
		$this->app->employee
            ->addActivity("Lead : '".$this['name']."' Activated", null/* Related Document ID*/, $this->id /*Related Contact ID*/,null,null,"xepan_marketing_leaddetails&contact_id=".$this->id."")
            ->notifyWhoCan('deactivate','Active',$this);
		$this->save();
	}


	//deactivate Lead
	function deactivate(){
		$this['status']='InActive';
		$this->app->employee
            ->addActivity("Lead : '".$this['name']."' has deactivated", null/* Related Document ID*/, $this->id /*Related Contact ID*/,null,null,"xepan_marketing_leaddetails&contact_id=".$this->id."")
            ->notifyWhoCan('activate','InActive',$this);
		$this->save();
	}

	function rule_abcd($a){

	}

	//activate Lead

	function checkContactIsLead(){
		if(($this['type'] !='Contact') AND ($this['type'] !='Lead'))
			throw new \Exception("Sorry! you cannot delete ".$this['type']." from here");
	}

	function checkExistingOpportunities($m){				
		$opportunity = $this->add('xepan\marketing\Model_Opportunity');
		$opportunity->addCondition('lead_id',$this->id);
		$opportunity->tryLoadAny();

		if($opportunity->loaded())
			throw new \Exception('Cannot Delete,first delete lead`s opportunities');
	}

	function checkExistingCategoryAssociation($m){		
		$lead = $this->add('xepan\marketing\Model_Lead');
		$lead->load($this->id);

		if($lead->loaded())
			$lead->removeAssociateCategory();	
	}

	function getAssociatedCategories(){

		$associated_categories = $this->ref('xepan\marketing\Lead_Category_Association')
								->_dsql()->del('fields')->field('marketing_category_id')->getAll();
		return iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($associated_categories)),false);
	}

	function removeAssociateCategory(){
		$this->ref('xepan\marketing\Lead_Category_Association')->deleteAll();
	}

	function associateCategory($category){
		return $this->add('xepan\marketing\Model_Lead_Category_Association')
						->addCondition('lead_id',$this->id)
		     			->addCondition('marketing_category_id',$category)
			 			->tryLoadAny()	
			 			->save();
	}

	function page_send($page){
		$newsletter_m=$page->add('xepan\marketing\Model_Newsletter');
		$newsletter_m->addCondition('status','Approved');

		$f=$page->add('Form',null,null,['form/empty']);
		$newsletter_field=$f->addField('Dropdown','newsletter')->validate('required')->setEmptyText('Please Select Newsletter');
		$newsletter_field->setModel($newsletter_m);
		$newsletter_field->setAttr(['multiple'=>'multiple']);
		$mymail = $f->addField('Dropdown','email_username')->setEmptyText('Please Select From Email')->validate('required');
		$mymail->setModel('xepan\hr\Model_Post_Email_MyEmails');

		$source_mail = explode("<br/>",$this['emails_str']);
		foreach ($source_mail as $index => $email) {
			$email_for_letter = $f->addField('CheckBox',"email_".$index,$email);
		}

		$employee_mail = explode("<br/>",$this->app->employee['emails_str']);
		foreach ($employee_mail as $index => $emp_email) {
			$emp_email_for_letter = $f->addField('CheckBox',"emp_email_".$index,$emp_email);
		}

		$f->addSubmit('Send Newsletter')->addClass('btn btn-primary');
		
		$view = $page->add('View');
		if($this->app->stickyGET('newsletter')){
			$nws_ids = explode(',', $this->app->stickyGET('newsletter'));			
			$newsletter_m->addCondition('id',$nws_ids);
			
			foreach ($newsletter_m as $nws) {
				$view->add('View')->setHtml("<hr> <center> <b>".$nws['title']."</b></center><br><br>".$nws['message_blog']."<hr>");
			}
		}

		$newsletter_field->js('change',$view->js()->reload(['newsletter'=>$newsletter_field->js()->val()]));
		
		if($f->isSubmitted()){			
			$count = 0;
			foreach ($source_mail as $index => $email) {				
				if(!$f['email_'.$index])
					$count++;
			}

			if($count == count($source_mail)){
				$page->js()->univ()->alert('Please select atleast one email of lead')->execute();
			}

			if($f['email_username'] == null)
				throw $this->exception('From email is required','ValidityCheck')->setField('email_username');

			if($f['newsletter'] == null)
				throw $this->exception('Please select a newsletter','ValidityCheck')->setField('newsletter');

			$newsletter_ids = explode(',', $f['newsletter']);			
			$newsletter_model=$page->add('xepan\marketing\Model_Newsletter');
			$newsletter_model->addCondition('id',$newsletter_ids);
			
			$email_settings = $this->add('xepan\communication\Model_Communication_EmailSetting')->load($f['email_username']);

			foreach ($newsletter_model as $single_newsletter) {
				$mail = $this->add('xepan\marketing\Model_Communication_Newsletter');
				$mail['from_id'] = $this->app->employee->id;

				$subject = $single_newsletter['title'] ;		    		    
				$email_subject=$this->add('GiTemplate');
				
				$email_body = $single_newsletter['message_blog'];
				$email_subject->loadTemplateFromString($subject);
				$subject_v=$this->add('View',null,null,$email_subject);
				$subject_v->template->set($this->get());

				$pq = new \xepan\cms\phpQuery();
				$dom = $pq->newDocument($email_body);
				
				foreach ($dom['a'] as $anchor){
					$a = $pq->pq($anchor);
					$url = $this->app->url($a->attr('href'),['xepan_landing_contact_id'=>$this->id,'xepan_landing_campaign_id'=>$this['lead_campaing_id'],'xepan_landing_content_id'=>$single_newsletter->id,'xepan_landing_emailsetting_id'=>$email_settings['id'],'source'=>'NewsLetter'])->absolute()->getURL();
					$a->attr('href',$url);
				}
				
				$email_body = $dom->html();
				$temp=$this->add('GiTemplate');
				$temp->loadTemplateFromString($email_body);
				$body_v=$this->add('View',null,null,$temp);
				$body_v->template->set($this->get());
				$body_v->template->trySetHTML('unsubscribe','<a href='.$_SERVER["HTTP_HOST"].'/?page=xepan_marketing_unsubscribe&email_str='.$email.'&xepan_landing_contact_id='.$this->id.'&document_id='.$single_newsletter->id.'>Unsubscribe</>');

				$mail->setfrom($email_settings['from_email'],$email_settings['from_name']);

				foreach ($source_mail as $index => $email) {				
					if($f['email_'.$index])
						$mail->addTo($email);
				}

				foreach ($employee_mail as $index => $emp_email) {				
					if($f['emp_email_'.$index])
						$mail->addBcc($emp_email);
				}

				// Stop automatic activity creation by newsletter send email0
				$this->app->skipActivityCreate = true;

				$mail['related_document_id'] = $newsletter_model->id;
				$mail->setSubject($subject_v->getHtml());
				$mail->setBody($body_v->getHtml());
				$mail->send($email_settings);
				
				$this->app->employee
					->addActivity("Newsletter : '".$single_newsletter['content_name']."' successfully sent to '".$this['name']."'", $single_newsletter->id/* Related Document ID*/, /*Related Contact ID*/$this->id,null,null,"xepan_marketing_newsletterdesign&0&action=view&document_id=".$single_newsletter->id."")
					->notifyWhoCan(' ',' ',$this);
			}

			return $f->js(true,$f->js(null,$f->js()->closest('.dialog')->dialog('close'))->univ()->successMessage('Mail Send Successfully'))->reload();				
		}
	}

	function addLeadFromCSV($data){
		// multi record loop
			foreach ($data as $key => $record) {

				try{
					$this->api->db->beginTransaction();

					$email_array = ['personal'=>[],'official'=>[]];
					$contact_array = ['personal'=>[],'official'=>[]];
					$category = [];

					$lead = $this->add('xepan\marketing\Model_Lead');
					foreach ($record as $field => $value) {
						$field = strtolower(trim($field));
						$value = trim($value);

						// category selection
						if($field == "category" && $value){
							$category = explode(",",$value);
							continue;
						}

						// official contact
						if(strstr($field, 'official_contact') && $value){
							$contact_array['official'][] = $value;
							continue;
						}
						// official email
						if(strstr($field, 'official_email') && $value){
							$email_array['official'][] = $value;
							continue;
						}

						// Personal contact
						if(strstr($field, 'personal_contact') && $value){
							$contact_array['personal'][] = $value;
							continue;
						}
						// official email
						if(strstr($field, 'personal_email') && $value){
							$email_array['personal'][] = $value;
							continue;
						}

						if($field == "country"){
							$country = $this->add('xepan\base\Model_Country')->addCondition('name','like',$value)->tryLoadAny();
							if(!$country->loaded())
								continue;
							$value = $country->id;
						}

						if($field == "state"){
							$state = $this->add('xepan\base\Model_State')->addCondition('name','like',$value)->tryLoadAny();
							if(!$state->loaded())
								continue;
							$value = $state->id;
						}

						$lead[$field] = $value;
					}

					// try{
						$lead->save();
					// }catch(\Exception $e){
					// 	continue;
					// }

					// insert category
					foreach ($category as $key => $name) {
						$name = trim($name);

						$lead_category = $this->add('xepan\marketing\Model_MarketingCategory');
						$lead_category->addCondition('name',$name);
						$lead_category->tryLoadAny();
						// try{
							$lead_category->save();
						// }catch(\Exception $e){
						// 	continue;
						// }
						
						$lead_category_asso = $this->add('xepan\marketing\Model_Lead_Category_Association');
						$lead_category_asso->addCondition('lead_id',$lead->id);
						$lead_category_asso->addCondition('marketing_category_id',$lead_category->id);
						$lead_category_asso->tryLoadAny();
						
						// try{
							$lead_category_asso->save();
						// }catch(\Exception $e){

						// }
						// echo "cat = ".$lead_category['id']."<br/>";
					}

					// echo "<pre>";
					// print_r($category);
					// print_r($email_array);
					// print_r($contact_array);

					// insert email official ids			
					foreach ($email_array['official'] as $key => $email) {
						$email_model = $this->add('xepan\base\Model_Contact_Email');
						$email_model->addCondition('value',$email);
						$email_model->tryLoadAny();
						
						if(!$email_model->loaded()){
							$email_model['contact_id'] = $lead->id;
							$email_model['head'] = "Official";				
							$email_model['value'] = $email;
							$email_model->save();
						}
					}
					
					foreach ($email_array['personal'] as $key => $email) {
						$email_model = $this->add('xepan\base\Model_Contact_Email');
						$email_model['value'] = $email;
						$email_model->addCondition('value',$email);
						$email_model->tryLoadAny();
						
						if(!$email_model->loaded()){
							$email_model['contact_id'] = $lead->id;
							$email_model['head'] = "Personal";				
							$email_model['value'] = $email;
							$email_model->save();
						}
					}

					// insert offical contact numbers
					foreach($contact_array['official'] as $key => $contact){
						$phone = $this->add('xepan\base\Model_Contact_Phone');
						$phone->addCondition('value',$contact);
						$phone->tryLoadAny();

						if(!$phone->loaded()){
							$phone['contact_id'] = $lead->id;
							$phone['head'] = "Official";
							$phone->save();
						}
					}

					// insert offical contact numbers
					foreach($contact_array['personal'] as $key => $contact){
						$phone = $this->add('xepan\base\Model_Contact_Phone');
						$phone->addCondition('value',$contact);
						$phone->tryLoadAny();

						if(!$phone->loaded()){
							$phone['contact_id'] = $lead->id;
							$phone['head'] = "Personal";
							$phone->save();
						}
					}

					$lead->unload();

					$this->api->db->commit();
				}catch(\Exception $e){
					echo $e->getMessage()."<br/>";
					continue;
				// 	// throw $e;
					// $this->api->db->rollback();
				}
			}
	}

	function page_assign($page){

		$dept_id = $this->app->stickyGET('dept_id');
		
		$emp = $page->add('xepan\hr\Model_Employee');
		$emp->addCondition('id','<>',$this->app->employee->id);
		
		$form = $page->add('Form');

		$dept = $page->add('xepan\hr\Model_Department');
		$dept->addCondition('status','Active');

		$dept_field = $form->addField('xepan\base\DropDown','department');
		$dept_field->setModel($dept);
		$dept_field->setEmptyText('Please Select Department');

		$emp_field = $form->addField('xepan\base\DropDown','employee')->validate('required');
		$emp_field->setModel($emp);
		$emp_field->setEmptyText('Please Select');

		$form->addField('text','remark');

		if($this['assign_to_id'])
			$emp_field->set($this['assign_to_id']);

		if($dept_id){
			$emp_field->getModel()->addCondition('department_id',$dept_id);
		}

		$dept_field->js('change',$form->js()->atk4_form('reloadField','employee',[$this->app->url(),'dept_id'=>$dept_field->js()->val()]));
		// $dept_field->js('change',$emp_field->js()->reload(null,null,[$this->app->url(null,['cut_object'=>$emp_field->name]),'dept_id'=>$dept_field->js()->val()]));

		$form->addSubmit('Assign')->addClass('btn btn-primary');
		if($form->isSubmitted()){
			$this->assign($form['employee'],$form['remark']);
			return $this->app->page_action_result = $this->app->js(true,$page->js()->univ()->closeDialog())->univ()->successMessage('Assigned');
		}
	}

	function assign($assign_to_id,$remark=null){

		$this['assign_to_id'] = $assign_to_id;
		$this['remark'] = $remark;
		// $this['assign_at'] = $this->app->now; // done by before save hook in model contact
		$this->save();
		
		$employee = $this->add('xepan\base\Model_Contact')->load($assign_to_id);
		$remark = "Lead Assigned to ".$employee['name']." by ".$this->app->employee['name'];
		$this->add('xepan\communication\Model_Communication_Comment')
			->createNew($this->app->employee,$this,$remark,$remark,$on_date=$this->app->now);
		

		return $this;
	}
} 
