<?php

namespace xepan\marketing;

class Model_SocialPosters_Base_SocialConfig extends \xepan\base\Model_Table{
	
	public $table='marketingcampaign_socialconfig';

	function init(){
		parent::init();

		// $this->hasOne('xepan\base\Epan','epan_id');
		// $this->addCondition('epan_id',$this->app->epan->id);
		
		$this->addField('social_app')->mandatory(true)->system(true); // Must Be Set In Extended class

		$this->addField('name');
		$this->addField('appId')->type('text');
		$this->addField('secret')->type('text');
		$this->addField('post_in_groups')->type('boolean')->defaultValue(true);
		$this->addField('filter_repeated_posts')->type("boolean")->defaultValue(true);

		$this->addField('type')->defaultValue("SocialPosters_Base_SocialConfig");
		$this->addField('created_by_id')->defaultValue($this->app->employee->id);
		$this->addField('status')->setValueList(['Active'=>"Active","Inactive"=>"Inactive"])->defaultValue('Active');

		$this->hasMany('xepan/marketing/SocialPosters_Base_SocialUsers','config_id');

		$this->addHook('beforeDelete',$this);

		$this->add('dynamic_model/Controller_AutoCreator');
	}

	function beforeDelete(){
		$this->ref('xepan/marketing/SocialPosters_Base_SocialUsers')->deleteAll();
	}

	function inactive(){
		
	}

	function active(){
		
	}

	function getJsonModel($json=null){
		$model = $this->add('Model');
		if(!$json)
			$json = $this['extra'];
		
		$config_data = json_decode(($josn)?:'{}',true);
		$model->setSource("Array",$config_data);

		return $model;
	}
}