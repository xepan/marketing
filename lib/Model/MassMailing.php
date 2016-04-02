<?php

namespace xepan\marketing;

class Model_MassMailing extends \xepan\base\Model_Epan_EmailSetting{
	function init(){
		parent::init();

		$this->addCondition('mass_mail',true);
	}
}