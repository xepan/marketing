<?php
namespace xepan\marketing;
class page_newslettertemplate extends \Page{
	public $title="Newsletter Template";
	function init(){
		parent::init();	

		$newsletter = $this->add('xepan\marketing\Model_Newsletter');
		$newsletter->addCondition('is_template',true);
		$crud=$this->add('xepan\base\Grid',null/*['action_page'=>'xepan_marketing_newsletterdesign']*/,null,['grid/newslettertemplate-grid']);
		$crud->setModel($newsletter);
	}
}