<?php
namespace xepan\marketing;

class page_tests_003communicationtest extends \xepan\base\Page_Tester {
    public $title = 'Communication Tests';

    public $proper_responses=[
        'Test_checkWeekelyCommunicationData'=>'1,2,3,4'
    ];

    function test_checkWeekelyCommunicationData(){
        return "abcd";
    }
}