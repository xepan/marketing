<?php

namespace xepan\marketing;

class page_strategyplanning extends \xepan\base\Page{
	public $title = "Strategy Planning";
	function init(){
		parent::init();
		
		$this->js(true)
				->_load('mindchart/jquery.orgchart')
				->_load('mindchart/mindchart');
		$this->js(true)->_css('mindchart/jquery.orgchart');
		
		// JOSN CONFIG MODEL WITH SEPERATE FIELDS
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
		$config_m->add('xepan\hr\Controller_ACL');
		$config_m->tryLoadAny();

		// ORGANIZATION'S AUDIENCE MANAGEMENT
		$audience = $config_m['strategy_planning_target_audience']?:"{}";		
		$audience = json_decode($audience,true);
		$data = [];
		foreach ($audience as $key => $node) {
			$temp = $node['data'];
			// id:1, name:'Root', parent: 0, level: 0
			$data[] = ["id"=>$temp['id'],"name"=>$temp['name'],"parent"=>$temp['parent'],"level"=>$temp['level']]; 
		}

		if(!count($data))
			$data[]	= ["id"=> 1, "name"=> 'Root', "parent"=> 0, "level"=>1];
		
		$audience_view = $this->add('View',null,'audience');
		$audience_view->js(true)->xepan_mindchart(
									[	
										"data" => $data,
										"Labels"=>[
													["add"=>'Add Category'],
													["add"=>'Add Subcategory'],
													["add"=>'Add Example']
												],
										'field'=>'strategy_planning_target_audience',
										'maxLevel' => 4,
										'addbutton_false_at_level' => 4
									]);

		// ORGANIZATION'S LOCATION MANAGEMENT
		$location_crud = $this->add('xepan\base\CRUD',['frame_options'=>['width'=>'600px'],'entity_name'=>'Location'],'location');
		$location_crud->setModel($config_m,['country','state','city']);

		// ORGANIZATION'S BUSINESS DESCRIPTION MANAGEMENT
		$form = $this->add('Form',null,'business');
		$form->setModel($config_m,['business_description','business_stream','business_usp']);
		$form->addSubmit('Save');
		if($form->isSubmitted()){
			$form->model->save();
			$form->js()->univ()->successMessage("Saved Successfully")->execute();
		}

		// ORGANIZATION'S DIGITAL PRESENCE
		$digital_nodes = $config_m["strategy_planning_digital_presence"]?:"{}";
		$digital_nodes = json_decode($digital_nodes,true);
		$digital_data = [];
		foreach ($digital_nodes as $key => $node) {
			$temp = $node['data'];
			// id:1, name:'Root', parent: 0, level: 0
			$digital_data[] = ["id"=>$temp['id'],"name"=>$temp['name'],"parent"=>$temp['parent'],"level"=>$temp['level']]; 
		}

		if(!count($digital_data))
			$digital_data[]	= ["id"=> 1, "name"=> 'Digital Presence', "parent"=> 0, "level"=>1];
		
		$digital_view = $this->add('View',null,'digital');
		$digital_view->js(true)->xepan_mindchart(
									[	
										"data" => $digital_data,
										"Labels"=>[
													["add"=>'Add Platform'],
													["add"=>'Add Url']
												],
										'field'=>'strategy_planning_digital_presence',
										'maxLevel' => 3,
										'addbutton_false_at_level' => 3
									]);

		// ORGANIZATION'S COMPETITORS MANAGEMENT
		$competetors_crud = $this->add('xepan\base\CRUD',['frame_options'=>['width'=>'600px'],'entity_name'=>'Competetor'],'competetor');
		$competetors_crud->setModel($config_m,['competitor_name','competitor_url','competitor_description']);
	}

	function defaultTemplate(){
		return ["page/strategyplanning"];
	}

}