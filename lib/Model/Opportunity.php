<?php

namespace xepan\marketing;  

class Model_Opportunity extends \xepan\base\Model_Document{

	function init(){
		parent::init();

		$opp_j=$this->join('opportunity.document_id');
		$opp_j->hasOne('xepan\marketing\Lead','contact_id');
		$opp_j->addField('title');
		$opp_j->addField('duration');

	}
} 