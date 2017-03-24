<?php

namespace xepan\marketing;

class page_daybydayanalytics extends \xepan\base\Page{
	public $title = "Marketing Day by Day Analytics Dashboard";
    public $widget_list = [];
    public $entity_list = [];
    public $filter_form;

    function init() {
        parent::init();
        
        $rpt = $this->add('xepan\base\Model_GraphicalReport');
        $rpt->tryLoadBy('name','MarketingReport'); 
           
        $permitted_post = json_decode($rpt['permitted_post'],true);

        if((count($permitted_post) AND in_array($this->app->employee['post_id'], $permitted_post)) OR $this->app->employee['scope'] == 'SuperUser')    
            $runner_view = $this->add('xepan\hr\View_GraphicalReport_Runner',['report_id'=>$rpt->id]);   
    }

	function defaultTemplate(){
		return['page/mktngdashboard'];
	}
}