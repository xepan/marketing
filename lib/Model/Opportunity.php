<?php

namespace xepan\marketing;  

class Model_Opportunity extends \xepan\hr\Model_Document{

	public $status=[
		'Open',
		'Qualified',
		'NeedsAnalysis',
		'Quoted',
		'Negotiated',
		'Winned',
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
		
		$opp_j->hasOne('xepan\marketing\Lead','lead_id');
		$opp_j->hasOne('xepan\hr\Employee','assign_to_id');

		$opp_j->addField('title')->sortable(true);
		$opp_j->addField('description')->type('text');
		$opp_j->addField('fund')->type('money');
		$opp_j->addField('discount_percentage')->type('int');
		$opp_j->addField('closing_date');
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
		$form->addSubmit('Save');
		
		if($form->isSubmitted()){
			$this->quote($form['narration'],$form['probability_percentage']);
			$this->app->employee
				->addActivity("Quoted Opportunity", $this->id, $this['lead_id'],null,null,"xepan_marketing_leaddetails&contact_id=".$this['lead_id']."")
				->notifyWhoCan('negotiate,win,lose','Quoted');
			return $p->js()->univ()->closeDialog();
		}	
	}

	function quote($narration,$probability_percentage=0){
		$this['previous_status']= $this['status'];
		$this['status']='Quoted';
		$this['narration']=$narration;
		$this['probability_percentage']=$probability_percentage;
		$this->save();
		return true;
	}

	function page_negotiate($p){
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