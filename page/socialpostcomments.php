<?php
namespace xepan\marketing;
class page_socialpostcomments extends \xepan\base\Page{
	public $title="Social Posting Comments";
	function init(){
		parent::init();
		
		$posting_id=$this->app->stickyGET('posting_id');
		$social_activity=$this->add('xepan/marketing/Model_SocialPosters_Base_SocialActivity');
		$social_activity->addCondition('posting_id',$posting_id);
		$social_activity->addCondition('activity_type','Comment');
		$social_activity->tryLoadAny();		
		$grid=$this->add('xepan\hr\Grid',null,null,['grid/post-comments']);
		$grid->setModel($social_activity);

		$f=$this->add('Form');
		$f->addfield('text','comments');

	}
}