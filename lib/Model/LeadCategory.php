<?php

/**
* description: Lead Category is used to classify Leads
* 
* @author : Gowrav Vishwakarma
* @email : gowravvishwakarma@gmail.com, info@xavoc.com
* @website : http://xepan.org
* 
*/

namespace xepan\marketing;

class Model_LeadCategory extends \xepan\base\Model_Document{

	function init(){
		parent::init();
		
		$cat_j = $this->join('lead_category.document_id');
		$cat_j->addField('name');
		
		$cat_j->hasMany('xepan\marketing\Lead','lead_id');

		$this->addCondition('type','LeadCategory');

	}
}
