<?php



namespace xepan\marketing;

class page_tests_init extends \AbstractController{
	function init(){
		parent::init();

		$this->app->xepan_app_initiators['xepan\marketing']->resetDB();

		// ADDING CATEGORIES
		$categories=[];
		for ($i=0; $i <4 ; $i++) { 
       		$categories[$i] = $category = $this->add('xepan\marketing\Model_MarketingCategory');
	        $category['name'] = 'Category'.$i;
	        $category->save();
		}
		
        // ADDING Leads
		$leads=[]; 
        for ($i=0; $i <4 ; $i++) { 
        	$leads[$i] = $lead = $this->add('xepan\marketing\Model_Lead');
	        $lead['first_name']="Fname".$i;
	        $lead['last_name']="Lname".$i;
	        $lead->save();
	        $lead->associateCategory($categories[$i]->id);         	
        }

        // ADDING Opportunities
        for ($i=0; $i <4 ; $i++) { 
	        $opportunity = $this->add('xepan\marketing\Model_Opportunity');
	        $opportunity['lead_id'] = $leads[$i]->id;
	        $opportunity['title'] = "opportunity".$i;
	        $opportunity['description'] = "description".$i;
	        $opportunity['created_at'] = '2016-01-01';
	        $opportunity->save();     
        }

        // ADDING CAMPAIGNS
		for ($i=0; $i <4 ; $i++) { 
	        $campaign = $this->add('xepan\marketing\Model_Campaign');
	        $campaign['title'] = "title".$i;
	        $campaign['starting_date'] = "2016-01-01";
	        $campaign['ending_date'] = "2016-12-01";
	        $campaign['campaign_type'] = 'campaign';
	        $campaign->save(); 
	        $campaign->associateCategory($categories[$i]->id);     
    	}        
	}
}