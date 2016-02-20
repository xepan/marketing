<?php

namespace xepan\marketing;  

class Model_Lead extends \xepan\base\Model_Contact{

	function init(){
		parent::init();

		$lead_j=$this->join('lead.contact_id');
		$lead_j->addField('source');
		$lead_j->addField('communication');
		$lead_j->addField('category');
		$lead_j->addField('opportunities');
		//$lead_j->addField('commment');

	}
} 