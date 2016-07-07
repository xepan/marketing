<?php

namespace xepan\marketing;

class page_strategyplanning extends \xepan\base\Page{
	public $title = "Strategy Planning";
	function init(){
		parent::init();

		// // CONFIG KEY FOR SCHEDULE 
		// 	//1.	STRATEGY_PLANNING_TARGET_AUDIENCE, 
		// 	//2.	STRATEGY_PLANNING_TARGET_LOCATION, 
		// 	//3.	STRATEGY_PLANNING_BUSINES_DESCRIPTION, 
		// 	//4.	STRATEGY_PLANNING_DIGITAL_PRESENCE, 
		// 	//5.	STRATEGY_PLANNING_COMETETORS
		
		$this->js(true)
				->_load('mindchart/jquery.orgchart')
				->_load('mindchart/mindchart');
		$this->js(true)->_css('mindchart/jquery.orgchart');

		$config_model = $this->app->epan->config;
		
		// /**
		// AUDIENCE MANAGEMENT
		// CONFIG KEY -	STRATEGY_PLANNING_TARGET_AUDIENCE
		// */
		$nodes = $config_model->getConfig("STRATEGY_PLANNING_TARGET_AUDIENCE",'marketing')?:"{}";
		$nodes = json_decode($nodes,true);
		$data = [];
		foreach ($nodes as $key => $node) {
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
										'config_key'=>"STRATEGY_PLANNING_TARGET_AUDIENCE",
										'maxLevel' => 4,
										'addbutton_false_at_level' => 4
									]);
		
		// $audience_model = $audience_tab->add('xepan\base\Model_ConfigJsonModel',
		// 			[
		// 				'fields'=>[
		// 							'name'=>"Line",
		// 							],
		// 				'config_key'=>'STRATEGY_PLANNING_TARGET_AUDIENCE'
		// 			]);
		// $audience_crud = $audience_tab->add('xepan\base\CRUD',['frame_options'=>['width'=>'600px'],'entity_name'=>'Audience']);
		// $audience_crud->setModel($audience_model);


		// /**
		// LOCATION CRUD MANAGEMENT
		// CONFIG KEY -	STRATEGY_PLANNING_TARGET_LOCATION
		// */
		$location_model = $this->add('xepan\base\Model_ConfigJsonModel',
					[
						'fields'=>[
									'country'=>"Line",
									'state'=>"Line",
									'city'=>"Line"
									],
						'config_key'=>'STRATEGY_PLANNING_TARGET_LOCATION'
					]);
		$location_crud = $this->add('xepan\base\CRUD',['frame_options'=>['width'=>'600px'],'entity_name'=>'Location'],'location');
		$location_crud->setModel($location_model);

		/**
		BUSINESS DESCRIPTION CRUD MANAGEMENT
		CONFIG KEY -	STRATEGY_PLANNING_BUSINES_DESCRIPTION
		*/
		$business_model = $this->add('xepan\base\Model_ConfigJsonModel',
					[
						'fields'=>[
									'description'=>"text",
									'stream' => "text",
									"usp"=>"text"
									],
						'config_key'=>'STRATEGY_PLANNING_BUSINES_DESCRIPTION'
					]);
		$business_model->tryLoadAny();
		$form = $this->add('Form',null,'business');
		$form->setModel($business_model);
		$form->addSubmit('Save');
		if($form->isSubmitted()){
			$form->model->save();
			$form->js()->univ()->successMessage("Saved Successfully")->execute();
		}
		// $business_crud = $this->add('xepan\base\CRUD',['frame_options'=>['width'=>'600px'],'entity_name'=>'Business Description']);
		// $business_crud->setModel($business_model);

		// /**
		// DIGITAL MANAGEMENT
		// CONFIG KEY -	STRATEGY_PLANNING_DIGITAL_PRESENCE
		// */
		$digital_nodes = $config_model->getConfig("STRATEGY_PLANNING_DIGITAL_PRESENCE",'marketing')?:"{}";
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
										'config_key'=>"STRATEGY_PLANNING_DIGITAL_PRESENCE",
										'maxLevel' => 3,
										'addbutton_false_at_level' => 3
									]);
		// $digital_model = $digital_tab->add('xepan\base\Model_ConfigJsonModel',
		// 			[
		// 				'fields'=>[
		// 							'social_network'=>"Line",
		// 							'url' => "text"
		// 							],
		// 				'config_key'=>'STRATEGY_PLANNING_DIGITAL_PRESENCE'
		// 			]);
		// $digital_crud = $digital_tab->add('xepan\base\CRUD',['frame_options'=>['width'=>'600px'],'entity_name'=>'Digital Presence']);
		// $digital_crud->setModel($digital_model);

		// /**
		// COMPETETORS CRUD MANAGEMENT
		// CONFIG KEY -	STRATEGY_PLANNING_COMETETORS
		// */
		$competetors_model = $this->add('xepan\base\Model_ConfigJsonModel',
					[
						'fields'=>[
									'name'=>"Line",
									'url'=>"Line",
									'description' => "text"
									],
						'config_key'=>'STRATEGY_PLANNING_COMETETORS'
					]);
		$competetors_crud = $this->add('xepan\base\CRUD',['frame_options'=>['width'=>'600px'],'entity_name'=>'Competetor'],'competetor');
		$competetors_crud->setModel($competetors_model,['name','url','description']);

	}

	function defaultTemplate(){
		return ["page/strategyplanning"];
	}

}