<?php

namespace xepan\marketing;
	
class page_leadscore extends \xepan\base\Page{
	public $title = "Total Score";
	function init(){
		parent::init();

		$lead_id=$this->app->stickyGET('contact_id');
		
		$score=$this->add('xepan\base\Model_PointSystem');
		$score->addCondition('contact_id',$lead_id);
		$score->setOrder('created_at','desc');
		$grid = $this->add('xepan\base\Grid');
		$grid->setModel($score);
		$grid->addPaginator(50);
	}
}