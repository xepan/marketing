<?php

namespace xepan\marketing;

class page_getcategory extends \Page{
	function init(){
		parent::init();

		$c = $this->add('xepan\marketing\Model_MarketingCategory');

		$rows = $c->getRows(['id','name']);
		$option = "";
		foreach ($rows as $row) {
			$option .= "<option value='".$row['id']."'>".$row['name']."</option>";
		}

		echo $option;
		exit;
	}
}