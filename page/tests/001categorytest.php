<?php
namespace xepan\marketing;

class page_tests_001categorytest extends \xepan\base\Page_Tester {
    public $title = 'Category Tests';

    public $proper_responses=[
    	'-'=>'-'
    ];

    function init(){
        $this->add('xepan\marketing\page_tests_init');
        parent::init();
    }

    function prepare_CategoryCreation(){
        $this->proper_responses['Test_CategoryCreation']=[
            'Category0',
            'Category1',
            'Category2',
            'Category3'
        ];
    }

    function test_CategoryCreation(){

        for ($i=0; $i < 4; $i++) { 
            $this->category[$i] = $this->add('xepan\marketing\Model_MarketingCategory')
                                    ->loadBy('name','Category'.$i);
            
        }

        $result=[];
        for ($i=0; $i<4; $i++) {
            $result[] = $this->category[$i]['name'];
        }

        return $result;
    }

    function prepare_LeadCount(){
        $this->proper_responses['Test_LeadCount']=[
            'Category1'=>1, 
            'Category2'=>1, 
            'Category3'=>1, 
            'Category4'=>1, 
        ];
    }

    function test_LeadCount(){
                 
        return [
            'Category1'=>count($this->category[0]->getAssociatedLeads()),
            'Category2'=>count($this->category[1]->getAssociatedLeads()),
            'Category3'=>count($this->category[2]->getAssociatedLeads()),
            'Category4'=>count($this->category[3]->getAssociatedLeads())
        ];
    }

    function prepare_LeadCount2(){
        $this->proper_responses['Test_LeadCount2']=[
            'Category0'=>1, 
            'Category1'=>2, 
            'Category2'=>1, 
            'Category3'=>1, 
        ];   
    }

    function test_LeadCount2(){
                 
       $lead = $this->add('xepan\marketing\Model_Lead')
                    ->tryLoadAny();
       
       $lead->associateCategory($this->category[1]->id);

       return [
            'Category0'=>count($this->category[0]->getAssociatedLeads()),
            'Category1'=>count($this->category[1]->getAssociatedLeads()),
            'Category2'=>count($this->category[2]->getAssociatedLeads()),
            'Category3'=>count($this->category[3]->getAssociatedLeads())
        ];
    }

    function prepare_CategoryDelete(){
        $this->proper_responses['Test_CategoryDelete']=[
            'Category0'=>'Exception',
            'Category1'=>'Deleted',
            'Category2'=>'Exception',
            'Category3'=>'Exception'   
        ];
    }

    function test_CategoryDelete(){
        
        $this->category[1]->removeAssociatedLeads();
        $this->category[1]->removeAssociatedCampaigns();

        $result=[];        
        for ($i=0; $i < 4; $i++) { 
            try{
                $this->category[$i]->delete();
            }catch(\Exception $e){
                $result[$this->category[$i]['name']]='Exception';
                continue;
            }
            $result[$this->category[$i]['name']]='Deleted';
        }
        
        return $result;
    }

    function prepare_CampaignCount(){
        $this->proper_responses['Test_CampaignCount']=[
            'Category0'=>1, 
            'Category1'=>Exception, 
            'Category2'=>1, 
            'Category3'=>1, 
        ];
    }

    function test_CampaignCount(){
        
        $result=[];        
        for ($i=0; $i < 4; $i++) { 
            try{
                count($this->category[$i]->getAssociatedCampaigns());
            }catch(\Exception $e){
                $result[$this->category[$i]['name']]='Exception';
                continue;
            }
            $result[$this->category[$i]['name']]=count($this->category[$i]->getAssociatedCampaigns());
        }
        return $result;    
    }

    function prepare_CampaignCount2(){
        $this->proper_responses['Test_CampaignCount2']=[
            'Category0'=>2, 
            'Category1'=>Exception, 
            'Category2'=>2, 
            'Category3'=>1, 
        ];   
    }

    function test_CampaignCount2(){
       /*
            ERROR WHEN ASSOCIATING WITH 0th CATEGORY OTHERWISE WORKING FINE
       */
                 
       $campaign = $this->add('xepan\marketing\Model_Campaign')
                    ->tryLoadAny();
       $campaign->associateCategory($this->category[0]->id);       
       

       $result=[];        
        for ($i=0; $i < 4; $i++) { 
            try{
                count($this->category[$i]->getAssociatedCampaigns());
            }catch(\Exception $e){
                $result[$this->category[$i]['name']]='Exception';
                continue;
            }
            $result[$this->category[$i]['name']]=count($this->category[$i]->getAssociatedCampaigns());
        }
        return $result;   
    }    
}