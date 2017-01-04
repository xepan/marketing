<?php

namespace xepan\marketing;

class page_test extends \xepan\base\Page{
	function init(){
		parent::init();
		return;
		$category_name = ['Default',
					 'Active Affiliate',
					 'InActive Affiliate',
					 'Active Employee',
					 'InActive Employee',
					 'Active Customer',
					 'InActive Customer',
					 'Active Supplier',
					 'InActive Supplier',
					 'Active OutSourceParty',
					 'InActive OutSourceParty'
					];

       	
       	foreach ($category_name as $cat_name){
        	$mar_cat=$this->add('xepan\marketing\Model_MarketingCategory');
        	$mar_cat['name'] = $cat_name;
        	$mar_cat['system'] = true;
        	$mar_cat->save();			
       	}

       	$contact_type = ['Affiliate','Employee','Customer','Supplier','OutsourceParty'];
		
		foreach ($contact_type as $ct){
			$model = $this->add('xepan\base\Model_Contact');
			$model->addCondition('type',$ct);
		
			foreach ($model as $m){			
				$mar_cat1 = $this->add('xepan\marketing\Model_MarketingCategory');
				$cat_name = $m['status']." ".$ct;						
	        	$mar_cat1->loadBy('name',$cat_name);

	        	$new_cat_assoc1 = $this->add('xepan\marketing\Model_Lead_Category_Association');
				$new_cat_assoc1['lead_id'] = $m['id'];
				$new_cat_assoc1['marketing_category_id'] = $mar_cat1->id;
				$new_cat_assoc1->save();	
			}
		}		
	}
}