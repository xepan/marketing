<?php

namespace xepan\marketing;  

class Model_Content extends \xepan\hr\Model_Document{

	public $status=[
		'Draft',
		'Submitted',
		'Approved',
		'Rejected'
	];
	public $actions=[
		'Draft'=>['view','edit','delete','submit','test'],
		'Submitted'=>['view','reject','approve','edit','delete','test'],
		'Approved'=>['view','reject','schedule','edit','delete','test','get_url'],
		'Rejected'=>['view','edit','delete','submit','test']
	];

	function page_get_url($p){
		
		$form = $p->add('Form');
		$form->addField('page');
		$form->addField('source')->addClass('xepan-push-large');
		$form->addSubmit('Get traceable URL')->addClass('btn btn-primary xepan-push-large');
		$view = $p->add('View');
		
		$page='index';
		$source='Social';

		if($_GET['url']) $page=$_GET['url'];
		if($_GET['source']) $source=$_GET['source'];

		$view->set($this->app->pm->base_url."?page=$page&source=$source&xepan_landing_content_id=$this->id");

		if($form->isSubmitted()){
			$url = $form['input_your_websites_url'].'/?&xepan_landing_content_id='.$this->id;
			$view->js()->reload(['url'=>$form['page'],'source'=>$form['source']])->execute();
		}
	}

	function init(){
		parent::init();

		$cont_j=$this->join('content.document_id');
		$cont_j->hasone('xepan\marketing\MarketingCategory','marketing_category_id');
		$cont_j->addField('message_255')->type('text');
		$cont_j->addField('message_blog')->type('text')->display(['form'=>'xepan\base\RichText']);
		$cont_j->addField('url');
		$cont_j->addField('title');
		$cont_j->addField('content_name');
		$cont_j->addField('is_template')->type('boolean')->defaultValue(false);
		$cont_j->hasMany('xepan/marketing/Schedule','document_id');


		$this->getElement('status')->defaultValue('Draft');

		$this->is([
			'marketing_category_id|required',
			'title|required'
			]);

		$this->addHook('beforeDelete',[$this,'checkExistingSchedule']);
		$this->addHook('beforeSave',[$this,'updateSearchString']);


	}

	function updateSearchString($m){
		$search_string = ' ';
		$search_string .=" ". $this['title'];
		$search_string .=" ". $this['type'];
		$search_string .=" ". $this['message_255'];
		$search_string .=" ". $this['message_blog'];
		$search_string .=" ". $this['status'];

		$this['search_string'] = $search_string;
	}

	function checkExistingSchedule($m){
		$schedule_count = $m->ref('xepan/marketing/Schedule')->count()->getOne();

		if($schedule_count)
			throw new \Exception('Remove it from schedule first');
			
	}	
} 