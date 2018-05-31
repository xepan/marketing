<?php

namespace xepan\marketing;  

class Model_Opportunity extends \xepan\hr\Model_Document{

	public $status=[
		'Open',
		'Qualified',
		// 'NeedsAnalysis',
		'Quoted',
		// 'Negotiated',
		'Won',
		'Lost'
	];
	// public $actions=[
	// 	'Open'=>['view','edit','delete','qualify','lose','win','reassess','communication'],
	// 	'Qualified'=>['view','edit','delete','analyse_needs','win','lose','reassess','communication'],
	// 	'NeedsAnalysis'=>['view','edit','delete','quote','negotiate','lose','win','reassess','communication'],
	// 	'Quoted'=>['view','edit','delete','negotiate','win','lose','reassess','communication'],
	// 	'Negotiated'=>['view','edit','delete','quote','win','lose','reassess','communication'],
	// 	'Won'=>['view','edit','delete'],
	// 	'Lost'=>['view','edit','delete']
	// ];

	public $actions=[
		'Open'=>['view','qualify','lose','win','reassess','communication','edit','delete'],
		'Qualified'=>['view','quote','win','lose','reassess','communication','edit','delete',],
		// 'NeedsAnalysis'=>['view','edit','delete','quote','negotiate','lose','win','reassess','communication'],
		'Quoted'=>['view','win','lose','reassess','communication','edit','delete'],
		// 'Negotiated'=>['view','edit','delete','quote','win','lose','reassess','communication'],
		'Won'=>['view','edit','delete'],
		'Lost'=>['view','edit','delete']
	];
 
	function init(){
		parent::init();
		
		$this->opp_j = $opp_j=$this->join('opportunity.document_id');
		
		$opp_j->hasOne('xepan\marketing\Lead','lead_id')->display(['form'=>'xepan\base\Basic']);
		$opp_j->hasOne('xepan\hr\Employee','assign_to_id')->defaultValue($this->app->employee->id);

		$opp_j->addField('title')->sortable(true);
		$opp_j->addField('description')->type('text');
		$opp_j->addField('fund')->type('money');
		$opp_j->addField('discount_percentage')->type('int')->defaultValue(0);
		$opp_j->addField('closing_date')->type('date');
		$opp_j->addField('narration')->type('text');
		$opp_j->addField('previous_status');
		$opp_j->addField('probability_percentage');

		$this->addExpression('duration')->set('"TODO"');
		$this->addExpression('source')->set($this->refSql('lead_id')->fieldQuery('source'));
		$this->addExpression('effective_name')->set(function($m,$q){
			$lead = $this->add('xepan\marketing\Model_Lead');
			$lead->addCondition('id',$m->getElement('lead_id'));
			$lead->setLimit(1);
			return $lead->fieldQuery('effective_name');
		});

		$this->getElement('status')->defaultValue('Open');
		$this->addCondition('type','Opportunity');

		$this->addHook('beforeSave',[$this,'updateSearchString']);
	
		$this->is([
				'closing_date|required',
				'title|required'
		]);
	}

	function updateSearchString($m){

		$search_string = ' ';
		$search_string .=" ". $this['title'];
		$search_string .=" ". $this['description'];
		$search_string .=" ". $this['duration'];
		$search_string .=" ". $this['source'];

		$this['search_string'] = $search_string;
	}

	function page_communication($p){
		$lead = $this->add('xepan\marketing\Model_Lead');
		$lead->loadBy('id',$this['lead_id']);
		$lead->page_communication($p);
	}

	function page_qualify($p){
		$form = $p->add('Form');
		$form->addField('text','narration')->set($this['narration']);
		$form->addField('probability_percentage')->set($this['probability_percentage']);
		$form->addSubmit('Save');
		
		if($form->isSubmitted()){
			$this->qualify($form['narration'],$form['probability_percentage']);
			$this->app->employee
				->addActivity("Opportunity '".$this['title']."' Qualified", $this->id, $this['lead_id'],null,null,"xepan_marketing_leaddetails&contact_id=".$this['lead_id']."")
				->notifyWhoCan('analyse_needs,lose','Qualified');
			return $p->js()->univ()->closeDialog();
		}
	}	

	function qualify($narration,$probability_percentage=0){
		$this['previous_status']= $this['status'];
		$this['status']='Qualified';
		$this['narration']=$narration;
		$this['probability_percentage']=$probability_percentage;
		$this->save();
		return true;
	}

	function page_analyse_needs($p){
		$form = $p->add('Form');
		$form->addField('text','narration')->set($this['narration']);
		$form->addField('probability_percentage')->set($this['probability_percentage']);
		$form->addSubmit('Save');
		
		if($form->isSubmitted()){
			$this->analyse_needs($form['narration'],$form['probability_percentage']);
			$this->app->employee
				->addActivity("Opportunity : ".$this['title']." 's  Needs Analyzed", $this->id, $this['lead_id'],null,null,"xepan_marketing_leaddetails&contact_id=".$this['lead_id']."")
				->notifyWhoCan('quote,negotiate,lose','NeedsAnalysis');
			return $p->js()->univ()->closeDialog();
		}
	}


	function analyse_needs($narration,$probability_percentage=0){		
		$this['previous_status']= $this['status'];
		$this['status']='NeedsAnalysis';
		$this['narration']=$narration;
		$this['probability_percentage']=$probability_percentage;
		$this->save();
		return true;
	}

	function page_quote($p){
		$form = $p->add('Form');
		$form->addField('text','narration')->set($this['narration']);
		$form->addField('probability_percentage')->set($this['probability_percentage']);
		$form->addField('billing_address');
		$form->addField('DropDown','billing_country')->setModel('xepan\base\Country');
		$form->addField('DropDown','billing_state')->setModel('xepan\base\State');
		$form->addField('billing_city');
		$form->addField('billing_pincode');
		$form->addField('DatePicker','due_date');
		$form->addSubmit('Save');
		if($form->isSubmitted()){
			$quotation_model  = $this->quote($form->getAllFields());
			$this->app->employee
				->addActivity("Quoted to Opportunity '".$this['title']."'", $this->id, $this['lead_id'],null,null,"xepan_marketing_leaddetails&contact_id=".$this['lead_id']."")
				->notifyWhoCan('negotiate,win,lose','Quoted');
			return $this->app->page_action_result = $form->js()->univ()->frameURL('Quotation',$this->app->url('xepan_commerce_quotationdetail',['action'=>'edit','document_id'=>$quotation_model->id]));		
		}	
	}

	function quote($form_data){

		$quotation  = $this->add('xepan\commerce\Model_Quotation');
		$quotation['contact_id'] = $this['lead_id'];
		$quotation['related_qsp_master_id'] = $this->id;
		$quotation['billing_address'] = $form_data['billing_address'];
		$quotation['billing_country_id'] = $form_data['billing_country'];
		$quotation['billing_state_id'] = $form_data['billing_state'];
		$quotation['billing_city'] = $form_data['billing_city'];
		$quotation['billing_pincode'] = $form_data['billing_pincode'];
		$quotation['currency_id'] = $this->app->epan->default_currency->id;
		$quotation['exchange_rate'] = 1;
		$quotation['due_date'] = $form_data['due_date'];
		$quotation->save();

		$this['previous_status']= $this['status'];
		$this['status']='Quoted';
		$this['narration']= $form_data['narration'];
		$this['probability_percentage']= $form_data['probability_percentage'];
		$this->save();

		return $quotation;
	}

	function page_negotiate($p){
		$quotation = $this->add('xepan\commerce\Model_Quotation');
		$quotation->addCondition('related_qsp_master_id',$this->id);
		$quotation->tryLoadAny();

		if($quotation->loaded()){
			$view = $p->add('View');	
			$view->setHTML('Lead Already Quoted with<br> <b>Discount Amount :</b> '.'<b>'.$quotation['discount_amount'].'</b><br>'.' <b>Net Amount : </b> '.'<b>'.$quotation['net_amount'].'</b><br>'.'<b><span style = "cursor:pointer; cursor: hand; max-width:600px;" class ="view-quotation-detail small" data-quotation-id ='.$quotation['id'].' data-id = '.$quotation['id'].'>click to view detail</a></b><hr>');
			$view->js('click')->_selector('.view-quotation-detail')->univ()->frameURL('Quotation Details',[$this->api->url('xepan_commerce_quotationdetail'),'document_id'=>$view->js()->_selectorThis()->closest('[data-quotation-id]')->data('id')]);
		}

		$form = $p->add('Form');
		$form->addField('text','narration')->set($this['narration']);
		$form->addField('probability_percentage')->set($this['probability_percentage']);
		$form->addSubmit('Save');
		
		if($form->isSubmitted()){
			$this->negotiate($form['narration'],$form['probability_percentage']);
			$this->app->employee
				->addActivity("Negotiated with Opportunity '".$this['title']."'", $this->id, $this['lead_id'],null,null,"xepan_marketing_leaddetails&contact_id=".$this['lead_id']."")
				->notifyWhoCan('win,quote,lose','Negotiated');
			return $p->js()->univ()->closeDialog();
		}	
	}

	function negotiate($narration,$probability_percentage=0){
		$this['previous_status']= $this['status'];
		$this['status']='Negotiated';
		$this['narration']=$narration;
		$this['probability_percentage']=$probability_percentage;
		$this->save();
		return true;
	}

	function page_win($p){
		$form = $p->add('Form');
		$form->addField('text','narration')->set($this['narration']);
		$form->addSubmit('Save');
		
		if($form->isSubmitted()){
			$this->win($form['narration'],$form['probability_percentage']);
			$this->app->employee
				->addActivity("Won Opportunity : '".$this['title']."'", $this->id, $this['lead_id'],null,null,"xepan_marketing_leaddetails&contact_id=".$this['lead_id']."");			
			return $p->js()->univ()->closeDialog();
		}	
	}

	function win($narration){
		$this['previous_status']= $this['status'];
		$this['status']='Won';
		$this['narration']=$narration;
		$this->save();
		return true;
	}

	function page_lose($p){
		$form = $p->add('Form');
		$form->addField('text','narration')->set($this['narration']);
		$form->addSubmit('Save');
		
		if($form->isSubmitted()){
			$this->lose($form['narration'],$form['probability_percentage']);
			$this->app->employee
				->addActivity("Lost Opportunity : '".$this['title']."'", $this->id, $this['lead_id'],null,null,"xepan_marketing_leaddetails&contact_id=".$this['lead_id']."");
			return $p->js()->univ()->closeDialog();	
		}
	}

	function lose($narration){
		$this['previous_status']= $this['status'];
		$this['status']='Lost';
		$this['narration']=$narration;
		$this->save();
		return true;
	}

	function page_reassess($p){
		$form = $p->add('Form');
		$form->addField('text','narration')->set($this['narration']);
		$form->addField('fund')->set($this['fund']);
		$form->addField('discount_percentage')->set($this['discount_percentage']);
		$form->addField('DatePicker','closing_date')->set($this['closing_date']);
		$form->addSubmit('Save');
		
		if($form->isSubmitted()){
			$this->reassess($form['fund'],$form['discount_percentage'],$form['narration'],$form['closing_date']);
			$this->app->employee
				->addActivity("Reassessed Opportunity : '".$this['title']."'", $this->id, $this['lead_id'],null,null,"xepan_marketing_leaddetails&contact_id=".$this['lead_id']."");
			return $p->js()->univ()->closeDialog();	
		}
	}

	function reassess($fund,$discount_percentage,$narration,$closing_date){
		$this['fund']= $fund;
		$this['discount_percentage']=$discount_percentage;
		$this['narration']=$narration;
		$this['closing_date']=$closing_date;
		$this->save();
		return true;
	}
} 