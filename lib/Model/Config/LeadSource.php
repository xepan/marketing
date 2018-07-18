<?php

namespace xepan\marketing;

class Model_Config_LeadSource extends \xepan\base\Model_ConfigJsonModel{
	public $fields = [
					'lead_source'=>'text',
				];
	public $config_key = 'MARKETING_LEAD_SOURCE';
	public $application='marketing';

	function init(){
		parent::init();

	}
}