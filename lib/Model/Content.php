<?php

namespace xepan\marketing;  

class Model_Content extends \xepan\base\Model_Document{

	public $status=[
		'Draft',
		'Submitted',
		'Approved',
		'Rejected'
	];
	public $actions=[
		'Draft'=>['view','edit','delete','submit','test'],
		'Submitted'=>['view','reject','approve','edit','delete','test'],
		'Approved'=>['view','reject','email','edit','delete','test'],
		'Rejected'=>['view','edit','delete','submit','test']
	];

	function init(){
		parent::init();

		$cont_j=$this->join('content.document_id');
		$cont_j->hasone('xepan\marketing\MarketingCategory','marketing_category_id');
		$cont_j->addField('message_160')->type('text');
		$cont_j->addField('message_255')->type('text');
		$cont_j->addField('message_3000')->type('text')->display(['form'=>'xepan\base\RichText']);
		$cont_j->addField('message_blog')->type('text')->display(['form'=>'xepan\base\RichText']);
		$cont_j->addField('url');
		$cont_j->addField('title');
		$cont_j->addField('is_template')->type('boolean')->defaultValue(false);
		$cont_j->hasMany('xepan/marketing/Schedule','document_id');


		$this->getElement('status')->defaultValue('Draft');

		$this->addHook('beforeDelete',[$this,'checkExistingSchedule']);
	}

	function checkExistingSchedule($m){
		$schedule_count = $m->ref('xepan/marketing/Schedule')->count()->getOne();

		if($schedule_count)
			throw $this->exception('Cannot Delete,first delete  Schedule`s ');
	}	


} 