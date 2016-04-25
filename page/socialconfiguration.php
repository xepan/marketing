<?php
namespace xepan\marketing;

class page_socialconfiguration extends \Page{
	public $title="Social";
	function init(){
		parent::init();
		
		$tabs = $this->add('Tabs');

		$objects = ['Facebook'];
		// $objects = scandir($plug_path = getcwd()."../vendor");
    	foreach ($objects as $object) {
    	// 	if ($object != "." && $object != "..") {
     //    		if (filetype($plug_path.DS.$object) != "dir"){
     //    			$object = str_replace(".php", "", $object);
        			$t=$tabs->addTab($object);
        			// $login_status_view =$t->add('View');
        			$social = $t->add('xepan/marketing/SocialPosters_'.$object);
        			$social->config_page();
        			// $login_status_view->setHTML($object. ' - '. $social->login_status());
     //    		}
    	// 	}
    	}

	}

	// function defaultTemplate(){

		// return['page/socialconfiguration'];
	// }
}


