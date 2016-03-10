<?php

namespace xepan\marketing;  

class Model_SocialUser extends \xepan\base\Model_Table{
	public $table = "socialuser";
	public $status=[
	];
	public $actions=[
		'*'=>[
			'add',
			'view',
			'edit',
			'delete'
		]
	];
	public $acl=false; 

	function init(){
		parent::init();

		$this->addField('name');
		$this->addField('configuration');
		$this->hasMany('xepan/marketing/Campaign_SocialUser_Association','socialuser_id');		
		$this->addHook('beforeSave',$this);
		$this->addHook('beforeDelete',$this);

	}

	function beforeSave($m){}

	function beforeDelete($m){
		$campaign_social_user_count = $m->ref('xepan\marketing\Campaign_SocialUser_Association')->count()->getOne();
		
		if($campaign_social_user_count)
			throw $this->exception('Cannot Delete,first delete Campaign`s Social Users ');	
	}
} 