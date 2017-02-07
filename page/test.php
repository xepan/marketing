<?php

namespace xepan\marketing;

class page_test extends \xepan\base\Page{
	function init(){
		parent::init();
		
		$all_emails = [-1];
		$existing_email=$this->add('xepan\base\Model_Contact_Email');
		$existing_email->addCondition('value',$all_emails);
		
		$grid = $this->add('xepan\hr\Grid');
		$grid->setModel($existing_email);
		
		// $lead_cat_assoc = $this->add('xepan\marketing\Model_Lead_Category_Association');
		
		// $array = [];
		// foreach ($lead_cat_assoc as $model){

		// 	if(!isset($array[$model['lead_id']]))
		// 		$array[$model['lead_id']] = [];

		// 	if(!isset($array[$model['lead_id']][$model['marketing_category_id']])){
		// 		$array[$model['lead_id']][$model['marketing_category_id']] = $model['marketing_category_id'];
		// 	}else{
		// 		$model->delete();
		// 	}
		// }
	}
}