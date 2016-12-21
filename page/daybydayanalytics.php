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
        $rpt->tryLoadBy('name','DayByDayAnalytics'); 
        
        $runner_view = $this->add('xepan\hr\View_GraphicalReport_Runner',['report_id'=>$rpt->id]);   
    }

	function defaultTemplate(){
		return['page/mktngdashboard'];
	}
}