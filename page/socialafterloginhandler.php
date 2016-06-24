<?php
namespace xepan\marketing;

class page_socialafterloginhandler extends \xepan\base\Page{
	
	function init(){
		parent::init();

		if(!$_GET['xfrom']){
			$this->add('View')->set('no xfrom found');
			return;
		}
		
		$cont = $this->add('xepan/marketing/SocialPosters_'.$_GET['xfrom']);
		if(!$cont->after_login_handler()){
			$this->add('View_Error')->set('Please click the above URL');

		}else{
			$this->add('View_Info')->set('Access Token Updated');
		}
	}
}