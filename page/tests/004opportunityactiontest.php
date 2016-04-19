<?php
namespace xepan\marketing;

class page_tests_004opportunityactiontest extends \xepan\base\Page_Tester {
    public $title = 'Opportunity Status Tests';

    public $proper_responses=[
        'Test_ActionConvert'=>'Converted',
        'Test_ActionReject'=>'Rejected',
        'Test_ActionOpen'=>'Open',
        'Test_ActionConvert2'=>'Converted',
        'Test_ActionReject2'=>'Rejected',
        'Test_ActionOpen2'=>'Open'
        
    ];

    function prepare(){
        
        $this->add('xepan\marketing\page_tests_init');
        
        $this->lead = $lead =  $this->add('xepan\marketing\Model_Lead');
        $lead['first_name']="Lead1";
        $lead['last_name']="Lead1";
        $lead->save();

        $this->opportunity = $opportunity = $this->add('xepan\marketing\Model_Opportunity');
        $opportunity['lead_id'] = $this->lead->id;
        $opportunity['title'] = "Test opportunity";
        $opportunity['description'] = "Test description of opportunity";
        $opportunity['created_at'] = '2016-01-01';
        $opportunity->save(); 

    }

    function test_ActionConvert(){
        $this->opportunity->convert();
        $this->opportunity->reload();

        return $this->opportunity['status'];
    }

    function test_ActionReject(){
        $this->opportunity->reject();
        $this->opportunity->reload();

        return $this->opportunity['status'];
    }

    function test_ActionOpen(){
        $this->opportunity->open();
        $this->opportunity->reload();

        return $this->opportunity['status'];
    }


    function test_ActionConvert2(){
        $this->opportunity->convert();
        $this->opportunity->reload();

        return $this->opportunity['status'];
    }

    function test_ActionReject2(){
        $this->opportunity->reject();
        $this->opportunity->reload();

        return $this->opportunity['status'];
    }

    function test_ActionOpen2(){
        $this->opportunity->open();
        $this->opportunity->reload();

        return $this->opportunity['status'];
    }
}