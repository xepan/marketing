<?php
namespace xepan\marketing;

class SocialPosters_Facebook_FacebookConfig extends \xepan\marketing\Model_SocialPosters_Base_SocialConfig {
	function init(){
		parent::init();
		$this->getElement('social_app')->defaultValue('Facebook');
		$this->addCondition('social_app','Facebook');

	}
}