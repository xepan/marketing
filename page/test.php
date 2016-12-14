<?php

namespace xepan\marketing;

class page_test extends \xepan\base\Page{
	function init(){
		parent::init();

		$new_category_array = ['ERP','PRINTING','PRODUCTION'];
		$category_id_array = [];

		foreach ($new_category_array as $new_cat) {
			$category = $this->add('xepan\marketing\Model_MarketingCategory');
			$category['name'] = $new_cat;
			$category['system'] = true;
			$category->save();	
			$category_id_array [] = $category->id; 
		}

		$old_categories = $this->add('xepan\marketing\Model_MarketingCategory');

		foreach ($old_categories as $old_cat){
			if(substr($old_cat['name'],0,8) == 'Printing'){
				$cat = $this->add('xepan\marketing\Model_MarketingCategory');
				$cat['name'] = substr($old_cat['name'],20,25);
				$cat->save();

				$association = $this->add('xepan\marketing\Model_Lead_Category_Association');
				$association->addCondition('marketing_category_id',$old_cat->id);
				
				$lead_id = [];
				foreach ($association as $assoc){
					$lead_id[] =  ['id' =>$assoc['lead_id'], 'cat_name'=>$cat['name']];
					$assoc ->delete(); 
				}

				$camp_assoc = $this->add('xepan\marketing\Model_Campaign_Category_Association');
				$camp_assoc->addCondition('marketing_category_id',$old_cat->id);
				
				foreach ($camp_assoc as $ca){
					$ca->delete();
				}

				$old_cat->delete();
				if(!empty($lead_id)){
					foreach ($lead_id as $lead){
						$marketingcategory = $this->add('xepan\marketing\Model_MarketingCategory');
						$marketingcategory->addCondition([['id',$category_id_array],['name',$lead['cat_name']]]);

						foreach ($marketingcategory as $c){
							$new_cat_assoc = $this->add('xepan\marketing\Model_Lead_Category_Association');
							$new_cat_assoc['lead_id'] = $lead['id'];
							$new_cat_assoc['marketing_category_id'] = $c->id;
							$new_cat_assoc->save();	
						}
					}
				}
			}				
		}
	}
}