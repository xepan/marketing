<?php

namespace xepan\marketing;

class Model_LandingResponse extends \xepan\base\Model_Table{
	public $table = "landingresponse";
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

		$this->hasOne('xepan\marketing\Campaign','campaign_id');
		$this->hasOne('xepan\marketing\Content','content_id');
		$this->hasOne('xepan\base\Contact','contact_id');
		
		$this->hasOne('xepan\communication\Communication_EmailSetting','emailsetting_id');
		$this->hasOne('xepan\marketing\SocialPosters_Base_SocialUsers','social_user_id');

		$this->addField('date')->type('datetime');
		$this->addField('action'); // use as per requirement
		$this->addField('type')->hint('call/newsletter/sitevisit/email....');
		$this->addField('ip');
		$this->addField('latitude');
		$this->addField('longitude');
		$this->addField('referrersite');
	}
}