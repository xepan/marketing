<?php

namespace xepan\marketing;

class View_PostComments extends \View{
	function init(){
		parent::init();

		$posting_id = $this->app->stickyGET('posting_id');
		
		if(!$posting_id){
			return;
		}

		$model_posting_activity = $this->add('xepan\marketing\Model_SocialPosters_Base_SocialActivity');
		$model_posting_activity->addCondition('posting_id', $posting_id);

		$form = $this->add('Form',null,'form');
		$form->addField('text','add_comment')->addClass('xepan-push-small');
		$form->addSubmit('Add')->addClass('btn btn-primary btn-block');

		$comment_grid = $this->add('xepan\hr\grid',null,'grid',['grid\social-comments']);
		$comment_grid->setModel($model_posting_activity);
	}
}