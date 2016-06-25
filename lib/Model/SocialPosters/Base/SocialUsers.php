<?php

namespace xepan\marketing;

class Model_SocialPosters_Base_SocialUsers extends \xepan\base\Model_Table{
	public $table='marketingcampaign_socialusers';

	function init(){
		parent::init();
		
		$this->hasOne('xepan\base\Epan','epan_id');

		$this->hasOne('xepan\marketing\SocialPosters_Base_SocialConfig','config_id');
		
		$this->addField('name');
		$this->addField('userid'); // Used for profile in case different then api returned userid like facebook
		$this->addField('userid_returned');
		$this->addField('access_token')->system(false)->type('text');
		$this->addField('access_token_secret')->system(false)->type('text');
		$this->addField('access_token_expiry')->system(false)->type('datetime');
		$this->addField('is_access_token_valid')->type('boolean')->defaultValue(false)->system(true);
		$this->addField('is_active')->type('boolean')->defaultValue(true);

		$this->add('dynamic_model/Controller_AutoCreator');
	}
}