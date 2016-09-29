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

		if(!$_POST['field']){
			echo "false Field not found";
			exit;
		}

		$config_m = $this->add('xepan\base\Model_ConfigJsonModel',
		[
			'fields'=>[
						'strategy_planning_target_audience'=>'text',
						'country'=>'line',
						'state'=>'line',
						'city'=>'line',
						'business_description'=>'text',
						'business_stream' =>'text',
						'business_usp'=>'text',
						'strategy_planning_digital_presence'=>'text',
						'competitor_name'=>"Line",
						'competitor_url'=>"Line",
						'competitor_description' => "text"
						],
				'config_key'=>'ORGANIZATIONS_STRATEGY_PLANNING',
				'application'=>'marketing'
		]);
		$config_m->tryLoadAny();
		$config_m[$_POST['field']] = $_POST['nodes'];
		$config_m->save();
		// $config_model = $this->app->epan->config;
		// $config_model->setConfig($_POST['config_key'],$_POST['nodes']);
						
		echo true;
		exit;
	}
}