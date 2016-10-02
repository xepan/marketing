<?php

namespace xepan\marketing;  

class Model_Opportunity extends \xepan\hr\Model_Document{

	public $status=[
		'Open',
		'Qualified',
		'NeedsAnalysis',
		'Quoted',
		'Negotiated',
		'Won',
		'Lost'
	];
	public $actions=[
		'Open'=>['view','edit','delete','qualify','lose'],
		'Qualified'=>['view','edit','delete','analyse_needs','lose'],
		'NeedsAnalysis'=>['view','edit','delete','quote','negotiate','lose'],
		'Quoted'=>['view','edit','delete','negotiate','win','lose'],
		'Negotiated'=>['view','edit','delete','win','quote','lose'],
		'Won'=>['view','edit','delete'],
		'Lost'=>['view','edit','delete']
	];

	function init(){
		parent::init();
		
		$opp_j=$this->join('opportunity.document_id');
		
		$opp_j->hasOne('xepan\marketing\Lead','lead_id')->display(['form'=>'xepan\base\Basic']);
		$opp_j->hasOne('xepan\hr\Employee','assign_to_id');

		$opp_j->addField('title')->sortable(true);
		$opp_j->addField('description')->type('text');
		$opp_j->addField('fund')->type('money');
		$opp_j->addField('discount_percentage')->type('int');
		$opp_j->addField('closing_date')->type('date');
		$opp_j->addField('narration')->type('text');
		$opp_j->addField('previous_status');
		$opp_j->addField('probability_percentage');

		$this->addExpression('duration')->set('"TODO"');
		$this->addExpression('source')->set($this->refSql('lead_id')->fieldQuery('source'));

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

	function page_qualify($p){
		$form = $p->add('Form');
		$form->addField('text','narration')->set($this['narration']);
		$form->addField('probability_percentage')->set($this['probability_percentage']);
		$form->addSubmit('Save');
		
		if($form->isSubmitted()){
			$this->qualify($form['narration'],$form['probability_percentage']);
			$this->app->employee
				->addActivity("Qualified Opportunity", $this->id, $this['lead_id'],null,null,"xepan_marketing_leaddetails&contact_id=".$this['lead_id']."")
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
				->addActivity("analysing needs of Opportunity", $this->id, $this['lead_id'],null,null,"xepan_marketing_leaddetails&contact_id=".$this['lead_id']."")
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
				->addActivity("Quoted Opportunity", $this->id, $this['lead_id'],null,null,"xepan_marketing_leaddetails&contact_id=".$this['lead_id']."")
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
				->addActivity("Negotiated Opportunity", $this->id, $this['lead_id'],null,null,"xepan_marketing_leaddetails&contact_id=".$this['lead_id']."")
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
				->addActivity("won Opportunity", $this->id, $this['lead_id'],null,null,"xepan_marketing_leaddetails&contact_id=".$this['lead_id']."");			
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
				->addActivity("Lost Opportunity", $this->id, $this['lead_id'],null,null,"xepan_marketing_leaddetails&contact_id=".$this['lead_id']."");
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
} 