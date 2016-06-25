<?php
namespace xepan\marketing;
class page_socialpost extends \xepan\base\Page{
	public $title="Social Posting";
	function init(){
		parent::init();
		
		$post_id=$this->app->stickyGET('post_id');
		$social = $this->add('xepan\marketing\Model_SocialPosters_Base_SocialPosting');
		$social->addCondition('post_id',$post_id);
		$social->tryLoadAny();
		$crud=$this->add('xepan\hr\CRUD',null,null,['grid/total-posting-grid']);
		$crud->setModel($social);

		if(!$crud->isEditing()){
			$crud->grid->js('click')->_selector('.do-view-post-comment')->univ()->frameURL('Employee Details',[$this->api->url('xepan_marketing_socialpostcomments'),'posting_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
		}

	}
}