<?php

namespace xepan\marketing;

class page_scheduledetail extends \xepan\base\Page{
	public $title = "Schedule Detail";
	public $breadcrumb=['Home'=>'index','Campaign'=>'xepan_marketing_campaign','Schedule Detail'=>'#'];

	function init(){
		parent::init();

		$schedule = $this->add('xepan\marketing\Model_Schedule');
		$schedule->addCondition('campaign_id',$_GET['campaign_id']);
		
		$schedule->addExpression('title')->set(function($m,$q){
			$content = $this->add('xepan\marketing\Model_Content');	
			$content->addCondition('id',$m->getElement('document_id'));
			$content->setLimit(1);
			return $content->fieldQuery('title');
		});	

		$grid = $this->add('xepan\hr\Grid');
		$grid->setModel($schedule,['title','date','day','posted_on','content_type']);
		$grid->addFormatter('title','ShortText');
	}
}