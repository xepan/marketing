<?php

namespace xepan\marketing;  

class Model_Lead extends \xepan\base\Model_Contact{

	function init(){
		parent::init();

		$lead_j=$this->join('Lead.contact_id');
		$lead_j->addField('source');
		$lead_j->addField('commment');

	}
} 