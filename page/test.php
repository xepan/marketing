<?php

namespace xepan\marketing;

class page_test extends \xepan\base\Page{
	function init(){
		parent::init();
		
		$associate_and_keep_category = ['For ERP: Bhilwara textile','MACHINE MANUFACTURING IN INDIA','MANUFACTURING INDUSTRIES','Sales And Marketing Application','UCCI MEMBERS','UDAIPUR MACHINE MANUFACTURING INDUSTRIES','AHMEDABAD PRINTING INDUSTRIES','BANGALORE PRINTING INDUSTRIES'];
		
		$cat_name = ['PRINTING','PRODUCTION','ERP'];
		foreach ($associate_and_keep_category as $keep_cat){
			$mar_cat = $this->add('xepan\marketing\Model_MarketingCategory');			
			$mar_cat->loadBy('name',$keep_cat);

			$lead_cat_assoc = $this->add('xepan\marketing\Model_Lead_Category_Association'); 
			$lead_cat_assoc->addCondition('marketing_category_id',$mar_cat->id);

			foreach ($cat_name as $cn){
				$mar_cat1 = $this->add('xepan\marketing\Model_MarketingCategory');
				$mar_cat1->loadBy('name',$cn);					
				
				foreach ($lead_cat_assoc as $assoc){
					$lead_cat_assoc1 = $this->add('xepan\marketing\Model_Lead_Category_Association'); 
					$lead_cat_assoc1['lead_id'] = $assoc['lead_id'];
					$lead_cat_assoc1['marketing_category_id'] = $mar_cat1->id;
					$lead_cat_assoc1->save();	
				}
			}
		}


		$associate_and_delete_category = ['AHMEDABAD PRINTING INDUSTRIES'];
		$cat_name1 = ['Ahemedabaad'];
		foreach ($associate_and_delete_category as $delete_cat){
			$mar_cat2 = $this->add('xepan\marketing\Model_MarketingCategory');
			$mar_cat2->loadBy('name',$delete_cat);

			$lead_cat_assoc2 = $ths->add('xepan\marketing\Model_Lead_Category_Association'); 
			$lead_cat_assoc2->addCondition('marketing_category_id',$mar_cat2->id);

			foreach ($cat_name1 as $cn){
				$mar_cat3 = $this->add('xepan\marketing\Model_MarketingCategory');
				$mar_cat3->loadBy('name',$cn);					
				
				foreach ($lead_cat_assoc2 as $assoc){
					$lead_cat_assoc3 = $this->add('xepan\marketing\Model_Lead_Category_Association'); 
					$lead_cat_assoc3['lead_id'] = $assoc['lead_id'];
					$lead_cat_assoc3['marketing_category_id'] = $mar_cat3->id;
					$lead_cat_assoc3->save();	
				}
			}
		}		
	}
}