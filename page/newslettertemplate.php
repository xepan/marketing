<?php
namespace xepan\marketing;
class page_newslettertemplate extends \xepan\base\Page{
	public $title="Newsletter Template";
	public $breadcrumb=['Home'=>'index','Newsletter'=>'xepan_marketing_newslettertemplate','Template'=>'#'];

	function init(){
		parent::init();	

		$newsletter = $this->add('xepan\marketing\Model_Newsletter');
		$newsletter->addCondition('is_template',true);
		$newsletter->tryLoadAny();		
		$crud=$this->add('xepan\base\Grid',null,null,['grid/newslettertemplate-grid']);
		$crud->setModel($newsletter);
	}
}