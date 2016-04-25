<?php

namespace xepan\marketing;

class Model_SocialUsers extends \Model_Table{
	public $table='marketingcampaign_socialusers';

	function init(){
		parent::init();
		$this->hasOne('xepan\marketing/SocialConfig','config_id');
		
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