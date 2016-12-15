<?php

namespace xepan\marketing;

class page_test extends \xepan\base\Page{
	function init(){
		parent::init();
		
		// categories whose leads are wanted
		$category_array = ['MANUFACTURING INDUSTRIES', 
						  'Sales And Marketing Application',
						  'UCCI MEMBERS', 
						  'For ERP: Bhilwara textile',
						  'UDAIPUR MACHINE MANUFACTURING INDUSTRIES',
						  'MACHINE MANUFACTURING IN INDIA'];
		

		// gathering ids of categories 
		$marketing_cateogry_id = []; 
		foreach ($category_array as $cat){
			$marketing_category = $this->add('xepan\marketing\Model_MarketingCategory');
			$marketing_category->loadBy('name',$cat);
			$marketing_cateogry_id [] = $marketing_category->id; 
		}

		// gathering leads of cateogries from association
		$lead_cat_assoc = $this->add('xepan\marketing\Model_Lead_Category_Association');
		$lead_cat_assoc->addCondition('marketing_category_id',$marketing_cateogry_id);

		$lead_id = [];
		foreach ($lead_cat_assoc as $assoc){
			$lead_id [] = $assoc['lead_id'];
		}

		// getting id of printing category
		$cat_name = 'PRINTING';
		$category = $this->add('xepan\marketing\Model_MarketingCategory');
		$category->loadBy('name',$cat_name);
	
		// deleting association with printing category
		$lead_printing_assoc = $this->add('xepan\marketing\Model_Lead_Category_Association');
		$lead_printing_assoc->addCondition('marketing_category_id',$category->id);	
		$lead_printing_assoc->addCondition('lead_id',$lead_id);

		throw new \Exception($lead_printing_assoc->count());
		
		foreach ($lead_cat_assoc as $assoc){
			$assoc->delete();
		}		
	}
}