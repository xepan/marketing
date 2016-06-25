<?php

namespace xepan\marketing;

class Model_SocialPost extends \xepan\marketing\Model_Content{


	function init(){
		parent::init();
		

		$this->getElement('status')->defaultValue('Draft');
		$this->addCondition('created_by_id',$this->app->employee->id);
		$this->addCondition('type','SocialPost');

		$this->addExpression('total_posting')->set(function($m,$q){
			return $this->add('xepan\marketing\Model_SocialPosters_Base_SocialPosting')
					->addCondition('post_id',$q->getField('id'))
					->count();
		});

		$this->addExpression('total_likes')->set(function($m,$q){
			$model_socialposting = $this->add('xepan\marketing\Model_SocialPosters_Base_SocialPosting');
			$model_socialposting->addCondition('post_id',$q->getField('id'));
			return $model_socialposting->sum('likes');
		});

		$this->addExpression('total_shares')->set(function($m,$q){
			$model_socialposting = $this->add('xepan\marketing\Model_SocialPosters_Base_SocialPosting');
			$model_socialposting->addCondition('post_id',$q->getField('id'));
			return $model_socialposting->sum('share');
		});

		$this->addExpression('total_comments')->set(function($m,$q){
			$model_socialposting = $this->add('xepan\marketing\Model_SocialPosters_Base_SocialPosting');
			$model_socialposting->addCondition('post_id',$q->getField('id'));
			return $model_socialposting->sum('total_comments');
		});

		$this->hasMany('xepan\marketing\Model_SocialPosters_Base_SocialPosting','post_id');

	}

	function submit(){
		$this['status']='Submitted';
        $this->app->employee
            ->addActivity("Submitted Newsletter", $this->id)
            ->notifyWhoCan('approve,reject,test','Submitted');
        $this->saveAndUnload();    
	}

	function reject(){
		$this['status']='Rejected';
        $this->app->employee
            ->addActivity("Rejected Newsletter", $this->id)
            ->notifyWhoCan('submit,test','Rejected');
        $this->saveAndUnload();     
	}

	function approve(){
		$this['status']='Approved';
        $this->app->employee
            ->addActivity("Approved Newsletter", $this->id)
            ->notifyWhoCan('email,reject,test','Approved');
		$this->saveAndUnload(); 
	}
}
