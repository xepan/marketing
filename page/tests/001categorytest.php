<?php
namespace xepan\marketing;

class page_tests_001categorytest extends \xepan\base\Page_Tester {
    public $title = 'Category Tests';

    public $proper_responses=[
    	'-'=>'-'
    ];


    function prepare(){
        $this->add('xepan\marketing\page_tests_init');
    }

    function prepare_CategoryCreation(){
        $this->proper_responses['Test_CategoryCreation']=[
            'name'=> 'test_category'

        ];
    }

    function test_CategoryCreation(){

        $this->category = $this->add('xepan\marketing\Model_MarketingCategory');
        $this->category->loadBy('name','Category1');


        $result=[];
        foreach ($this->proper_responses['Test_CategoryCreation'] as $field => $value) {
            $result[$field] = $this->category[$field];
        }

        return $result;
    }
}