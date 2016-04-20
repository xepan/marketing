<?php
namespace xepan\marketing;

class page_tests_002leadtest extends \xepan\base\Page_Tester {
    public $title = 'Lead Tests';

    public $proper_responses=[
        '-'=>'-'
    ];

    function init(){
        $this->add('xepan\marketing\page_tests_init');
        parent::init();
    }
    
    function prepare_leadCreation(){
        $this->proper_responses['Test_leadCreation']=[
                        'epan_id'=>$this->app->epan->id,
                        'created_by_id'=>$this->app->employee->id,
                        'user_id'=>null,
                        'type'=>'Lead',
                        'name'=>'Fname1 Lname1',
                        'status'=>'Active',
                        'created_at'=>$this->app->now,
                        'open_count'=>1,
                        'converted_count'=>0,
                        'rejected_count'=>0,
                    ];
    }

    function test_leadCreation(){
        $this->lead = $this->add('xepan\marketing\Model_Lead')
                                    ->loadBy('name','Fname1 Lname1');                                                             
        $result=[];
        foreach ($this->proper_responses['Test_leadCreation'] as $field => $value) {
            $result[$field] = $this->lead[$field];            
        }

        return $result;
    }

    // function prepare_Opportunities(){
    //     $this->proper_responses['Test_Opportunities']=[
    //         'epan_id'=>$this->app->epan->id,
    //         'created_by_id'=>$this->app->employee->id,
    //         'user_id'=>null,
    //         'type'=>'Opportunity',
    //         'name'=>'Test Lead1 Fname Test Lead1 Sirname',
    //         'status'=>'Active',
    //         'created_at'=>$this->app->now,
    //         'source'=>$this->lead['source'],
    //         'status'=>"open"
    //     ];
    // }

    // function test_Opportunities(){
    //     $this->opportunity = $opportunity = $this->add('xepan\marketing\Model_Opportunity');
        
    //     $opportunity['lead_id'] = $this->lead->id;
    //     $opportunity['title'] = "Test opportunity";
    //     $opportunity['description'] = "Test description of opportunity";
    //     $opportunity['created_at'] = '2016-01-01';
    //     $opportunity->save(); 

    //     $result=[];
    //     foreach ($this->proper_responses['Test_leadCreation'] as $field => $value) {
    //         $result[$field] = $l[$field];
    //     }

    //     return $result;
    // }

    //  function prepare_Durations(){
    //     $this->proper_responses['Test_Durations']=[
    //         'duration_0'=>"Today",
    //         'duration_1'=>"5 Days",
    //         'duration_2'=>"20 Days",
    //         'duration_3'=>"1 Month",
    //         'duration_4'=>"10 days",
    //     ];
    // }

    // function test_Durations(){
    //     $result=[];
        
    //     $now = $this->app->now; 
    //     $today = $this->app->today;

    //     $this->app->today = '2016-01-01'; // 1 january        
    //     $o = $this->opportunity->reload();
    //     $result['duration_0'] = $o['duration'];

    //     $this->app->today = '2016-01-05'; // 5 january        
    //     $o = $this->opportunity->reload();
    //     $result['duration_1'] = $o['duration'];

    //     $this->app->today = '2016-01-20'; // 20 january        
    //     $o = $this->opportunity->reload();
    //     $result['duration_2'] = $o['duration'];


    //     $this->app->today = '2016-02-10'; // 10 feb        
    //     $o = $this->opportunity->reload();
    //     $result['duration_3'] = $o['duration'];


    //     $this->app->today = '2016-02-29'; // 29 feb        
    //     $o = $this->opportunity->reload();
    //     $result['duration_4'] = $o['duration'];

    //     $this->app->now = $now;
    //     $this->app->today = $today;
    //     return $result;
    // }

    // function prepare_StatusCount(){
    //     $this->proper_responses['Test_StatusCount']=[
    //     'open_count'=>3,
    //     'rejected_count'=>2,
    //     'converted_count'=>1
    //     ];
    // }


    // function test_StatusCount(){
       
    //    $status = ['Open','Converted','Rejected','Open','Rejected'];
    //    for ($i=0; $i <5 ; $i++) { 
    //         $opportunity = $this->add('xepan\marketing\Model_Opportunity');
    //         $opportunity['lead_id'] = $this->lead->id;
    //         $opportunity['title'] = "Test opportunity".$i;
    //         $opportunity['description'] = "Test description of opportunity".$i;
    //         $opportunity['created_at'] = '2016-01-01';
    //         $opportunity['status'] = $status[$i];
    //         $opportunity->save(); 
            
    //     } 

    //     $this->lead->reload();

    //     $result=[
    //     'open_count'=>$this->lead['open_count'],
    //     'rejected_count'=>$this->lead['rejected_count'],
    //     'converted_count'=>$this->lead['converted_count']
    //     ];
       
    //     return $result;
    // }


    // function prepare_Leaddelete(){
    //     $this->proper_responses['Test_Leaddelete']=[
    //        'opportunity_count'=>0,
    //        'deleted_lead_found'=>0,
    //        'related_deleted_contact_found'=>0 
    //     ];
    // }

    // function test_Leaddelete(){
    //     $lead_id = $this->lead->id;
    //     $this->lead->ref('Opportunities')->each(function($m){
    //         $m->delete();
    //     });

    //     $this->lead->delete();

    //     $result=[];
    //     $result['opportunity_count'] = $this->add('xepan\marketing\Model_Opportunity')
    //                               ->addCondition('lead_id',$lead_id)
    //                               ->count()->getOne(); 
    
    //     $result['deleted_lead_found'] = $this->app->db->dsql()
    //                     ->table('lead')
    //                     ->where('contact_id',$lead_id)
    //                     ->del('fields')
    //                     ->field('count(*)')
    //                     ->getOne();

    //     $result['related_deleted_contact_found'] = $this->app->db->dsql()
    //             ->table('contact')
    //             ->where('id',$lead_id)
    //             ->del('fields')
    //             ->field('count(*)')
    //             ->getOne();

    //     return $result;
    // }

    // function prepare_Opportunitydelete(){
    //     $this->proper_responses['Test_Opportunitydelete']=[
    //        'deleted_opportunity_found'=>0,
    //        'related_deleted_document_found'=>0 
    //     ];
    // }

    // function test_Opportunitydelete(){
    //     // Following Opportunity should have been removed from data base by deleting Lead
    //     $opportunity_id = $this->opportunity->id;

    //     $result=[];

    //     $result['deleted_opportunity_found'] = $this->app->db->dsql()
    //                     ->table('opportunity')
    //                     ->where('document_id',$opportunity_id)
    //                     ->del('fields')
    //                     ->field('count(*)')
    //                     ->getOne();

    //     $result['related_deleted_document_found'] = $this->app->db->dsql()
    //             ->table('document')
    //             ->where('id',$opportunity_id)
    //             ->del('fields')
    //             ->field('count(*)')
    //             ->getOne();

    //     return $result;

    // }

    // function prepare_FoundOrphanOpportunity(){
    //     $this->add('xepan\marketing\Model_Opportunity')->save();
    //     $this->proper_responses['Test_FoundOrphanOpportunity']=1;
    // }

    // function test_FoundOrphanOpportunity(){
        
    //     $opportunity = $this->add('xepan\marketing\Model_Opportunity');
    //     $opportunity->addExpression('actual_lead_id')->set(function($m,$q){
    //         return $m->refSQL('lead_id')->fieldQuery('id');
    //     });

    //     $opportunity->addCondition('actual_lead_id',null);
    //     $result =  $opportunity->count()->getOne();
    //     $opportunity->getElement('actual_lead_id')->destroy();
    //     $opportunity->each(function($m){$m->delete();});
    //     return $result;
    // }
}