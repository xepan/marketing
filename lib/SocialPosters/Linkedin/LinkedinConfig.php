<?php
namespace xepan\marketing;

class SocialPosters_Linkedin_LinkedinConfig extends \xepan\marketing\Model_SocialPosters_Base_SocialConfig {
	
	function init(){
		parent::init();

		$this->getElement('social_app')->defaultValue('Linkedin');
		$this->addCondition('social_app','Linkedin');

	}
}