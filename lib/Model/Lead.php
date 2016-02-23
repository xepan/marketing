<?php

namespace xepan\marketing;  

class Model_Lead extends \xepan\base\Model_Contact{

	function init(){
		parent::init();
		
		$lead_j = $this->join('lead.contact_id');
		$lead_j->hasOne('xepan\marketing\LeadCategory','category_id');
		$lead_j->addField('source');
		
		$this->addCondition('type','Lead');

	}
} 