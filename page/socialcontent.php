<?php
namespace xepan\marketing;
class page_socialcontent extends \Page{
	public $title="Social Content";
	function init(){
		parent::init();
		
		 $this->add('xepan\marketing\View_progressbar',null,'progressbar');
		 $this->add('xepan\marketing\View_social',['status'=>'submitted'],'social');
		 $this->add('xepan\marketing\View_social',null,'social');
		 $this->add('xepan\marketing\View_social',null,'social');
		 $this->add('xepan\marketing\View_social',null,'social');
	}

	function defaultTemplate(){

		return['page/socialcontent'];
	}
}