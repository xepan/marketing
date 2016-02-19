<?php

namespace xepan\marketing;  

class Model_SocialConfiguration extends \Model_Table{

	function init(){
		parent::init();

		$this->addField('name');
		$this->addField('configuration');		

	}
} 