<?php
namespace xepan\marketing;
class page_socialpostcomments extends \xepan\base\Page{
	public $title="Social Posting Comments";
	function init(){
		parent::init();
		
		$posting_id=$this->app->stickyGET('posting_id');
		$social_activity=$this->add('xepan/marketing/Model_SocialPosters_Base_SocialActivity');
		$social_activity->addCondition('posting_id',$posting_id);
		$social_activity->tryLoadAny();		
		$crud=$this->add('xepan\hr\CRUD');
		$crud->setModel($social_activity);

	}
}