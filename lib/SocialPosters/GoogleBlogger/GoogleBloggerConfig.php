<?php
namespace xepan\marketing;

class SocialPosters_GoogleBlogger_GoogleBloggerConfig extends \xepan\base\Model_Table {
	
	public $table='xmarketingcampaign_googlebloggerconfig';

	function init(){
		parent::init();

		$this->hasOne('xepan\base\Epan','epan_id');
		// $this->addCondition('epan_id',$this->app->epan->id);

		$this->addField('name');
		$this->addField('userid');
		$this->addField('userid_returned');
		$this->addField('blogid');
		$this->addField('appId')->display(array('grid'=>'shorttext,wrap'));
		$this->addField('secret');
		$this->addField('access_token')->system(false)->type('text');
		$this->addField('access_token_secret')->system(false)->type('text');
		$this->addField('refresh_token')->system(false)->type('text');
		$this->addField('is_access_token_valid')->type('boolean')->defaultValue(false)->system(true);
		$this->addField('is_active')->type('boolean')->defaultValue(true);

		$this->add('dynamic_model/Controller_AutoCreator');
	}
}