<?php

namespace xepan\marketing;

class View_PostComments extends \View{
	function init(){
		parent::init();

		$posting_id = $this->app->stickyGET('posting_id');
		
		if(!$posting_id){
			return;
		}

		$social_posting_model = $this->add('xepan\marketing\Model_SocialPosters_Base_SocialPosting')->load($posting_id);

		$model_posting_activity = $this->add('xepan\marketing\Model_SocialPosters_Base_SocialActivity');
		$model_posting_activity->addCondition('posting_id', $posting_id);

		$form = $this->add('Form',null,'form');
		$form->addField('text','comment')->validate('required')->addClass('xepan-push-small');
		$form->addSubmit('Add')->addClass('btn btn-primary btn-block');

		$comment_grid = $this->add('xepan\hr\grid',null,'grid',['grid\social-comments']);
		$comment_grid->setModel($model_posting_activity);
	
		if($form->isSubmitted()){
			try{
				$social_posters = $this->add('xepan\marketing\SocialPosters_'.$social_posting_model['social_app']);
				$social_posters->comment($social_posting_model,$form['comment']);
				$form->js()->univ()->successMessage("commented")->execute();
			}catch(Excepiton $e){
				throw new \Exception($e);
			}

		}
	}
}