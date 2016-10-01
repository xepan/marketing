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
		'Losed'
	];
	public $actions=[
		'Open'=>['view','edit','delete','qualify','lose'],
		'Qualified'=>['view','edit','delete','analyse_needs','lose'],
		'NeedsAnalysis'=>['view','edit','delete','quote','negotiate','lose'],
		'Quoted'=>['view','edit','delete','negotiate','win','lose'],
		'Negotiated'=>['view','edit','delete','win','quote','lose'],
		'Winned'=>['view','edit','delete'],
		'Losed'=>['view','edit','delete']
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
	
		// $this->is([
		// 		'closing_date|required',
		// 		'title|required'
		// ]);
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
			$new = $p->add('xepan\marketing\Model_Opportunity')->load($this->id);

			$new['previous_status'] = $this['status'];
			$new['status'] = 'Qualified';
			$new['narration']  = $form['narration'];
			$new['probability_percentage']  = $form['probability_percentage'];
			$new->save();
			// $this->app->employee
			// 	->addActivity("Qualified Opportunity", $this->id, $this['lead_id'],null,null,"xepan_marketing_leaddetails&contact_id=".$this['lead_id']."")
			// 	->notifyWhoCan('analyse_needs,lose','Qualified');
			$form->js()->univ()->successMessage('Opportunity Qualified')->execute();
		}
	}	

	function page_analyse_needs($p){
		$form = $p->add('Form');
		$form->setModel('xepan\marketing\Opportunity',['narration','probability_percentage'])->load($this->id);
		$form->addSubmit('Save');

		if($form->isSubmitted()){
			$form->save();
			$this['previous_status'] = $this['status'];
			$this['status'] = 'NeedsAnalysis';
			$this->app->employee
				->addActivity("analysing needs of Opportunity", $this->id, $this['lead_id'],null,null,"xepan_marketing_leaddetails&contact_id=".$this['lead_id']."")
				->notifyWhoCan('quote,negotiate,lose','NeedsAnalysis');
			$this->saveAndUnload();
		}	
	}

	function page_quote($p){
		$form = $p->add('Form');
		$form->setModel('xepan\marketing\Opportunity',['narration','probability_percentage'])->load($this->id);
		$form->addSubmit('Save');

		if($form->isSubmitted()){
			$form->save();
			$this['previous_status'] = $this['status'];
			$this['status'] = 'Quoted';
			$this->app->employee
				->addActivity("Quoted Opportunity", $this->id, $this['lead_id'],null,null,"xepan_marketing_leaddetails&contact_id=".$this['lead_id']."")
				->notifyWhoCan('negotiate,win,lose','Quoted');
			$this->saveAndUnload();
		}	
	}

	function page_negotiate($p){
		$form = $p->add('Form');
		$form->setModel('xepan\marketing\Opportunity',['narration','probability_percentage'])->load($this->id);
		$form->addSubmit('Save');

		if($form->isSubmitted()){
			$form->save();
			$this['previous_status'] = $this['status'];
			$this['status'] = 'Negotiated';
			$this->app->employee
				->addActivity("analysing needs of Opportunity", $this->id, $this['lead_id'],null,null,"xepan_marketing_leaddetails&contact_id=".$this['lead_id']."")
				->notifyWhoCan('win,quote,lose','Quoted');
			$this->saveAndUnload();
		}		
	}

	function page_win($p){
		$form = $p->add('Form');
		$form->setModel('xepan\marketing\Opportunity',['narration','probability_percentage'])->load($this->id);
		$form->addSubmit('Save');

		if($form->isSubmitted()){
			$form->save();
			$this['previous_status'] = $this['status'];
			$this['status'] = 'Winned';
			$this->app->employee
				->addActivity("analysing needs of Opportunity", $this->id, $this['lead_id'],null,null,"xepan_marketing_leaddetails&contact_id=".$this['lead_id']."");			
				$this->saveAndUnload();
		}	
	}

	function page_lose($p){
		$form = $p->add('Form');
		$form->setModel('xepan\marketing\Opportunity',['narration','probability_percentage'])->load($this->id);
		$form->addSubmit('Save');

		if($form->isSubmitted()){
			$form->save();
			$this['previous_status'] = $this['status'];
			$this['status'] = 'Losed';
			$this->app->employee
				->addActivity("analysing needs of Opportunity", $this->id, $this['lead_id'],null,null,"xepan_marketing_leaddetails&contact_id=".$this['lead_id']."");
			$this->saveAndUnload();
			
		}	
	}
} 