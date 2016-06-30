<?php

namespace xepan\marketing;
	
class page_strategysave extends \xepan\base\Page{
	public $title = "Strategy Save";
	
	function init(){
		parent::init();

		if(!$_POST['nodes']){
			echo "false nodes not found";
			exit;
		}

		if(!$_POST['config_key']){
			echo "false config key not found";
			exit;
		}

		$config_model = $this->app->epan->config;
		$config_model->setConfig($_POST['config_key'],$_POST['nodes'],'marketing');
						
		echo true;
		exit;
	}
}