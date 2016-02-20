<?php

namespace xepan\marketing;  

class Model_Content extends \xepan\base\Model_Document{

	function init(){
		parent::init();

		$cont_j=$this->join('Content.document_id');
		$cont_j->hasone('xepan\marketing\ContentCategory');
		$cont_j->addField('message');
		$cont_j->addField('blog');

	}
} 