<?php

namespace xepan\marketing;

class Model_SocialPosters_Base_SocialUsers extends \xepan\base\Model_Table{
	public $table='marketingcampaign_socialusers';
	// public $status = ['Active','Inactive'];
	// public $actions = [
	// 		'Active'=>['view','edit','delete','inactive'],
	// 		'Inactive'=>['view','edit','delete','active']
	// 	];

	function init(){
		parent::init();
		
		// $this->hasOne('xepan\base\Epan','epan_id');

		$this->hasOne('xepan\marketing\SocialPosters_Base_SocialConfig','config_id');
		
		$this->addField('name');
		$this->addField('userid'); // Used for profile in case different then api returned userid like facebook
		$this->addField('userid_returned');
		$this->addField('access_token')->system(false)->type('text');
		$this->addField('access_token_secret')->system(false)->type('text');
		$this->addField('access_token_expiry')->system(false)->type('datetime');
		$this->addField('is_access_token_valid')->type('boolean')->defaultValue(false)->system(true);
		$this->addField('is_active')->type('boolean')->defaultValue(true);
		$this->addField("extra")->defaultValue("{}"); // used for page and other management
		
		$this->addField('post_on_pages')->type('boolean')->defaultValue(true);
		$this->addField('post_on_timeline')->type('boolean')->defaultValue(true);
		// $this->add('dynamic_model/Controller_AutoCreator');
	}

	function appConfig(){
		if(!$this->loaded())
			throw new \Exception("model social user must loaded");
		
		return $this->add('xepan\marketing\Model_SocialPosters_Base_SocialConfig')->load($this['config_id']);
	}

	function getFBPages($only_postable_page=false){
		if(!$this->loaded())
			throw new \Exception("social user must loaded");
			
		$pages = json_decode($this['extra'],true);
		$saved_page_array = [];
		$saved_pages = isset($pages['data'])?$pages['data']:[];


		foreach ($saved_pages as $key => $page) {
			if($only_postable_page){
				if($page['send_post'])
					$saved_page_array[$page['id']] = ["name"=>$page['name'],"access_token"=>$page['access_token'],'send_post'=>$page['send_post']];
			}else
				$saved_page_array[$page['id']] = ["name"=>$page['name'],"access_token"=>$page['access_token'],'send_post'=>$page['send_post']];
		}
		return $saved_page_array;
	}

	function getLinkedinCompany($only_postable_page=false){
		if(!$this->loaded())
			throw new \Exception("social user must loaded");
			
		$pages = json_decode($this['extra'],true);
		$saved_page_array = [];
		$saved_pages = isset($pages['values'])?$pages['values']:[];


		foreach ($saved_pages as $key => $page) {
			if($only_postable_page){
				if($page['send_post'])
					$saved_page_array[$page['id']] = ["name"=>$page['name'],'send_post'=>$page['send_post']];
			}else
				$saved_page_array[$page['id']] = ["name"=>$page['name'],'send_post'=>$page['send_post']];
		}
		return $saved_page_array;	
	}

}