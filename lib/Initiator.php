<?php

namespace xepan\marketing;

class Initiator extends \Controller_Addon {
	public $addon_name = 'xepan_marketing';

	function init(){
		parent::init();
		$this->routePages('xepan_marketing');
		
	}
}
