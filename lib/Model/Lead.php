<?php

namespace xepan\marketing;  

class Model_Lead extends \xepan\base\Model_Contact{

	public $status = ['Active','InActive'];
	public $actions = [
					'Active'=>['view','edit','delete','deactivate','communication','send'],
					'InActive'=>['view','edit','delete','activate','communication']
					];

	function init(){
		parent::init();
		
		$this->getElement('created_by_id')->defaultValue($this->app->employee->id);
		
		// $lead_j = $this->join('lead.contact_id');
		// $lead_j->addField('source');
		// $lead_j->addField('remark')->type('text');
		// $lead_count=$lead_j->addExpression('count_leads')->set(function($m){
		// 	return $m->refSQL('xepan\marketing\Model_Opportunity')->count();
		// });

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

		$this->addExpression('score')->set(function($m,$q){
			$ps=$m->add('xepan\base\Model_PointSystem');
			$ps->addCondition('contact_id',$q->getField('id'));
			return $q->expr('IFNULL([0],0)',[$ps->sum('score')]);
		})->sortable(true);

		
		$this->hasMany('xepan\marketing\Opportunity','lead_id',null,'Opportunities');
		$this->hasMany('xepan\marketing\Lead_Category_Association','lead_id');
		
		$this->getElement('status')->defaultValue('Active');
		$this->addHook('beforeDelete',[$this,'checkExistingOpportunities']);
		$this->addHook('beforeDelete',[$this,'checkExistingCategoryAssociation']);
		$this->addHook('beforeSave',[$this,'updateSearchString']);

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
	
	function activate(){
		$this['status']='Active';
		$this->app->employee
            ->addActivity("Lead '".$this['name']."' now active", null/* Related Document ID*/, $this->id /*Related Contact ID*/,null,null,"xepan_marketing_leaddetails&contact_id=".$this->id."")
            ->notifyWhoCan('deactivate','Active',$this);
		$this->save();
	}


	//deactivate Lead
	function deactivate(){
		$this['status']='InActive';
		$this->app->employee
            ->addActivity("Lead '".$this['name']."' has deactivated", null/* Related Document ID*/, $this->id /*Related Contact ID*/,null,null,"xepan_marketing_leaddetails&contact_id=".$this->id."")
            ->notifyWhoCan('activate','InActive',$this);
		$this->save();
	}

	function rule_abcd($a){

	}

	//activate Lead

	function checkExistingOpportunities($m){
		$this->ref('Opportunities')->each(function($o){
			$o->delete();
		});

	}

	function checkExistingCategoryAssociation($m){
		$cat_ass_count = $this->ref('xepan\marketing\Lead_Category_Association')->count()->getOne();
		if($cat_ass_count)
			throw $this->exception('Cannot Delete,first delete Category Association`s ');	
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

	
		$source_mail = explode("<br/>",$this['emails_str']);
		foreach ($source_mail as $index => $email) {
			$email_for_letter = $f->addField('CheckBox',"email_".$index,$email);
		}

		$f->addSubmit('Send Newsletter')->addClass('btn btn-primary');
		
		if($this->app->stickyGET('newsletter')){
			$newsletter_m->tryLoad($this->app->stickyGET('newsletter'));
		}
		$view=$page->add('View')->addClass('xepan-padding-large');
		$view->setHtml($newsletter_m['title']."<br>".$newsletter_m['message_blog']);
		$newsletter_field->js('change',$view->js()->reload(['newsletter'=>$newsletter_field->js()->val()]));
		
		if($f->isSubmitted()){
			$newsletter_model=$page->add('xepan\marketing\Model_Newsletter');
			$newsletter_model->tryLoad($f['newsletter']);
			
			$email_settings = $this->add('xepan\communication\Model_Communication_EmailSetting')->tryLoadAny();
			$mail = $this->add('xepan\communication\Model_Communication_Email');

			$subject = $newsletter_model['title'] ;		    		    
			$email_subject=$this->add('GiTemplate');
			
			$email_body = $newsletter_model['message_blog'];
			$email_subject->loadTemplateFromString($subject);
			$subject_v=$this->add('View',null,null,$email_subject);
			$subject_v->template->set($this->get());

			$pq = new \xepan\cms\phpQuery();
			$dom = $pq->newDocument($email_body);
			foreach ($dom['a'] as $anchor){
				$a = $pq->pq($anchor);
				$url = $this->app->url($a->attr('href'),['xepan_landing_contact_id'=>$this->id,'xepan_landing_campaign_id'=>$this['lead_campaing_id'],'xepan_landing_content_id'=>$newsletter_model->id,'xepan_landing_emailsetting_id'=>$email_settings['id'],'source'=>'NewsLetter'])->absolute()->getURL();
				$a->attr('href',$url);
			}
			$email_body = $dom->html();
			$temp=$this->add('GiTemplate');
			$temp->loadTemplateFromString($email_body);
			$body_v=$this->add('View',null,null,$temp);
			$body_v->template->set($this->get());

			$mail->setfrom($email_settings['from_email'],$email_settings['from_name']);

			$source_mail = explode("<br/>",$this['emails_str']);
			foreach ($source_mail as $index => $email) {				
				if($f['email_'.$index])
					$mail->addTo($email);
			}
			
			$mail['related_document_id'] = $newsletter_model->id;
			$mail->setSubject($subject_v->getHtml());
			$mail->setBody($body_v->getHtml());
			$mail->send($email_settings);

			return $f->js(true,$f->js()->univ()->successMessage('Mail Send Successfully'))->reload();				
		}
	}

} 
