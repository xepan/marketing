<?php

namespace xepan\marketing;

class page_mylead extends \xepan\base\Page{
	public $title = "MY Lead`s"; 
	
	function init(){
		parent::init();

		$crud = $this->add('xepan\hr\CRUD',['allow_add'=>false,'allow_edit'=>false,'allow_del'=>false],null,['grid/lead-grid']);
		$mylead = $this->add('xepan\marketing\Model_Lead');
		$mylead->addCondition('assign_to_id',$this->app->employee->id);
		$mylead->setOrder('id','desc');
		$crud->setModel($mylead);
	}
}