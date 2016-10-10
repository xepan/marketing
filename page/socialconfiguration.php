<?php
namespace xepan\marketing;

class page_socialconfiguration extends page_configurationsidebar{
	public $title="Social Configuration";
	function init(){
		parent::init();
		
		$tabs = $this->add('Tabs');

		// $objects = ['Facebook','Linkedin'];
		$objects = scandir($plug_path = getcwd()."/../vendor/xepan/marketing/lib/SocialPosters");
    	foreach ($objects as $object) {
    		if ($object != "." && $object != "..") {
        		if (filetype($plug_path.'/'.$object) != "dir"){
        			$object = str_replace(".php", "", $object);
                    $t=$tabs->addTab($object);
        			$social = $t->add('xepan/marketing/SocialPosters_'.$object);
        			$social->config_page();
        			// $login_status_view->setHTML($object. ' - '. $social->login_status());
        		}
    		}
    	}

	}

	// function defaultTemplate(){

		// return['page/socialconfiguration'];
	// }
}


