<?php

namespace xepan\marketing;  

class Model_Opportunity extends \xepan\base\Model_document{

	function init(){
		parent::init();

		$this->hasOne('xepan\base\contact');
		$opp_j=$this->join('Opportunity.document_id');
		$opp_j->addField('details');
		$opp_j->addField('duration');

	}
} 