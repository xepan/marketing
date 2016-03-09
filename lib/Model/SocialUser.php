<?php

namespace xepan\marketing;  

class Model_SocialConfiguration extends \Model_Document{

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
		$this->hasMany('xepan/marketing/CampaignSocialUser','social_user_id');		

	}
} 