<?php
namespace xepan\marketing;

class page_socialloginmanager extends \xepan\base\Page{
	
	function init(){
		parent::init();

		if($social = $_GET['social_login_to']){
			$this->api->stickyGET('social_login_to');
			$message = $this->add('xepan/marketing/SocialPosters_'.$_GET['social_login_to'])->login_status();
					
			$this->add('View')->setHtml($message);
		}

	}
}