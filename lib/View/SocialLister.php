<?php

namespace xepan\marketing;

class View_SocialLister extends \CompleteLister{
	
	function init(){
		parent::init();

	}

	function formatRow(){
		// $m = parent::setModel($model);

		// $this->current_row_html[''] = $m[];
		// $this->current_row_html[''] = $m[];
		
		// return parent::formatRow();
	}

	function defaultTemplate(){
		return['view\sociallister'];
	}
}