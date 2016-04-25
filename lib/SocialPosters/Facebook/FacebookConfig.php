<?php
namespace xepan\marketing;

class Model_FacebookConfig extends \xepan\marketing\Model_SocialConfig {
	function init(){
		parent::init();
		$this->getElement('social_app')->defaultValue('Facebook');
		$this->addCondition('social_app','Facebook');

	}
}