<?php

namespace xepan\marketing;  

class Model_Opportunity extends \xepan\base\Model_document{

	function init(){
		parent::init();

		$opp_j=$this->join('Opportunity.document_id');
		$opp_j->addField('duration');
		$opp_j->addField('details');

	}
} 