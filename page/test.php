<?php

namespace xepan\marketing;

class page_test extends \xepan\base\Page{
	function init(){
		parent::init();
		
		$lead_cat_assoc = $this->add('xepan\marketing\Model_Lead_Category_Association');
		
		$array = [];
		foreach ($lead_cat_assoc as $model){

			if(!isset($array[$model['lead_id']]))
				$array[$model['lead_id']] = [];

			if(!isset($array[$model['lead_id']][$model['marketing_category_id']])){
				$array[$model['lead_id']][$model['marketing_category_id']] = $model['marketing_category_id'];
			}else{
				$model->delete();
			}
		}
	}
}