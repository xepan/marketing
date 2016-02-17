<?php

class page_marketing_socialcontent extends Page{

	function init(){
		parent::init();

		 $this->add('View_Marketing_progressbar',null,'progressbar');
		 $this->add('View_Marketing_social',['status'=>'submitted'],'social');
		 $this->add('View_Marketing_social',null,'social');
		 $this->add('View_Marketing_social',null,'social');
		 $this->add('View_Marketing_social',null,'social');
	}

	function defaultTemplate(){

		return['page/marketing/socialcontent'];
	}
}