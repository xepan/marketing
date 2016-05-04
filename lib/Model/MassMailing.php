<?php

namespace xepan\marketing;

class Model_MassMailing extends \xepan\communication\Model_Communication_EmailSetting{
	function init(){
		parent::init();

		$this->addCondition('mass_mail',true);
	}
}