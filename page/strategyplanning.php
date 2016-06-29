<?php

namespace xepan\marketing;

class page_strategyplanning extends \xepan\base\Page{
	public $title = "Strategy Planning";
	function init(){
		parent::init();

		$tab = $this->add('Tabs');
		$audience_tab = $tab->addTab('Audience');
		$location_tab = $tab->addTab('Location');
		$business_tab = $tab->addTab('Business Description');
		$digital_tab = $tab->addTab('Digital Presence');
		$competetor_tab = $tab->addTab('Competetors');

		// CONFIG KEY FOR SCHEDULE 
			//1.	STRATEGY_PLANNING_TARGET_AUDIENCE, 
			//2.	STRATEGY_PLANNING_TARGET_LOCATION, 
			//3.	STRATEGY_PLANNING_BUSINES_DESCRIPTION, 
			//4.	STRATEGY_PLANNING_DIGITAL_PRESENCE, 
			//5.	STRATEGY_PLANNING_COMETETORS
		
		/**
		AUDIENCE CRUD MANAGEMENT
		CONFIG KEY -	STRATEGY_PLANNING_TARGET_AUDIENCE
		*/

		$audience_model = $audience_tab->add('xepan\base\Model_ConfigJsonModel',
					[
						'fields'=>[
									'name'=>"Line",
									],
						'config_key'=>'STRATEGY_PLANNING_TARGET_AUDIENCE'
					]);
		$audience_crud = $audience_tab->add('xepan\base\CRUD',['frame_options'=>['width'=>'600px'],'entity_name'=>'Audience']);
		$audience_crud->setModel($audience_model);


		/**
		LOCATION CRUD MANAGEMENT
		CONFIG KEY -	STRATEGY_PLANNING_TARGET_LOCATION
		*/
		$location_model = $location_tab->add('xepan\base\Model_ConfigJsonModel',
					[
						'fields'=>[
									'country'=>"Line",
									'state'=>"Line",
									'city'=>"Line"
									],
						'config_key'=>'STRATEGY_PLANNING_TARGET_LOCATION'
					]);
		$location_crud = $location_tab->add('xepan\base\CRUD',['frame_options'=>['width'=>'600px'],'entity_name'=>'Location']);
		$location_crud->setModel($location_model);

		/**
		BUSINESS DESCRIPTION CRUD MANAGEMENT
		CONFIG KEY -	STRATEGY_PLANNING_BUSINES_DESCRIPTION
		*/
		$business_model = $business_tab->add('xepan\base\Model_ConfigJsonModel',
					[
						'fields'=>[
									'description'=>"text",
									'stream' => "text"
									],
						'config_key'=>'STRATEGY_PLANNING_BUSINES_DESCRIPTION'
					]);
		$business_crud = $business_tab->add('xepan\base\CRUD',['frame_options'=>['width'=>'600px'],'entity_name'=>'Business Description']);
		$business_crud->setModel($business_model);

		/**
		DIGITAL CRUD MANAGEMENT
		CONFIG KEY -	STRATEGY_PLANNING_DIGITAL_PRESENCE
		*/
		$digital_model = $digital_tab->add('xepan\base\Model_ConfigJsonModel',
					[
						'fields'=>[
									'social_network'=>"Line",
									'url' => "text"
									],
						'config_key'=>'STRATEGY_PLANNING_DIGITAL_PRESENCE'
					]);
		$digital_crud = $digital_tab->add('xepan\base\CRUD',['frame_options'=>['width'=>'600px'],'entity_name'=>'Digital Presence']);
		$digital_crud->setModel($digital_model);

		/**
		COMPETETORS CRUD MANAGEMENT
		CONFIG KEY -	STRATEGY_PLANNING_COMETETORS
		*/
		$competetors_model = $competetor_tab->add('xepan\base\Model_ConfigJsonModel',
					[
						'fields'=>[
									'name'=>"Line",
									'description' => "text"
									],
						'config_key'=>'STRATEGY_PLANNING_COMETETORS'
					]);
		$competetors_crud = $competetor_tab->add('xepan\base\CRUD',['frame_options'=>['width'=>'600px'],'entity_name'=>'Competetor']);
		$competetors_crud->setModel($competetors_model);

		
		$this->js(true)
				->_load('mindchart/jquery.orgchart')
				->_load('mindchart/mindchart');
		$this->js(true)->_css('mindchart/jquery.orgchart');

		$audience_view = $audience_tab->add('View')->set('Audience');
		$audience_view->js(true)->xepan_mindchart(
									[
										"Labels"=>[
													["add"=>'Add Category'],
													["add"=>'Add Subcategory'],
													["add"=>'Add Example']
												]
									]);

	}
}