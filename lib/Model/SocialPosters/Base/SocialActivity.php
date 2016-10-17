<?php

namespace xepan\marketing;
// Model Post Activity/Comments
class Model_SocialPosters_Base_SocialActivity extends \xepan\base\Model_Table{
	public $table = "marketingcampaign_socialpostings_activities";
	public $acl=false;
	function init(){
		parent::init();

		// $this->hasOne('xepan\base\Epan','epan_id');
		$this->hasOne('xepan/marketing/SocialPosters_Base_SocialPosting','posting_id');

		$this->addField('activityid_returned');
		$this->addField('activity_type');
		$this->addField('activity_on')->type('datetime'); // NOT DEFAuLT .. MUst get WHEN actual activity happened from social sites

		$this->addField('is_read')->type('boolean')->defaultValue(false);// is read
		$this->addField('activity_by');// Get the user from social site who did it.. might be an id of the user on that social site
		$this->addField('name')->caption('Activity')->allowHTML(true);
		$this->addField('action_allowed')->defaultValue(''); // Can remove/ can edit etc if done by user itself

		$this->add('dynamic_model/Controller_AutoCreator');		
	}

}
