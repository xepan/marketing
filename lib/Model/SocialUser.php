<?php

namespace xepan\marketing;

class Model_SocialUser extends \xepan\marketing\Model_SocialPosters_Base_SocialUsers{

	function init(){
		parent::init();

		$this->addExpression('type')->set(function($m,$q){
			return $this->add('xepan\marketing\Model_SocialPosters_Base_SocialConfig')
						->addCondition('id',$q->getField('config_id'))
						->fieldQuery('social_app');
		});
	}
}