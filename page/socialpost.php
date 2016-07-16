<?php
namespace xepan\marketing;
class page_socialpost extends \xepan\base\Page{
	public $title="Social Posting";
	function init(){
		parent::init();
		
		$post_id = $this->app->stickyGET('post_id');
		$social = $this->add('xepan\marketing\Model_SocialPosters_Base_SocialPosting');
		$social->addCondition('post_id',$post_id);
		$social->tryLoadAny();
		$grid = $this->add('xepan\hr\Grid',null,'grid',['grid/total-posting-grid']);
		$grid->setModel($social);
		$grid->add('xepan\hr\Controller_ACL');

		$model_post = $this->add('xepan\marketing\Model_SocialPost')->load($post_id);		
		$grid->template->trySet('post_title',$model_post['title']);
		$grid->template->trySetHtml('post_content',$model_post['message_blog']);
		$grid->template->trySet('post_url',$model_post['url']);	
		
		$grid->addHook('formatRow',function($g){
 			$g->current_row['monitoring']= $g->model['is_monitoring'];
 			$g->current_row['forcemonitoring']= $g->model['force_monitor'];
 		});

		$comment_view = $this->add('xepan\marketing\View_PostComments',null,'view',['view\postcomments']);
		$comment_view_url = $this->api->url(null,['cut_object'=>$comment_view->name]);

		$grid->js('click',$comment_view->js()->reload(['posting_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')],null,$comment_view_url))->_selector('.post-comments');
		
		$grid->on('click','.update-activity-button',function($js,$data)use($grid,$post_id){
			$model_post = $this->add('xepan\marketing\Model_SocialPost')->load($post_id);
			if(!$model_post->loaded()){
				throw new \Exception("No posts to update");
			}

			$model_post->updateActivity();
			return $this->js()->univ()->successMessage('Updated');
		});
	}

	function defaultTemplate(){
		return ['page\socialposting'];
	}
}