<?php
namespace xepan\marketing;

class page_socialexec extends \xepan\base\Page{

	function init(){
		parent::init();


		$all_schedule = $this->add('xepan/marketing/Model_Schedule');
		$all_schedule->addCondition('date','<=',date('Y-m-d H:i:s'));

		foreach ($all_schedule as $schedule) {			
			$campaign = $schedule->campaign();
			$social_users = $schedule->campaignSocialUser();

			foreach ($social_users as $user) {				
				$config = $user['configuration'];
				$social_app = "Facebook";
				$this->add('xepan/marketing/Controller_SocialPosters_'.$social_app)
						->postSingle(
								$social_users,
								$schedule->ref('document_id'),
								$config['post_in_groups'], 
								$groups_posted=array(),
								$under_campaign_id=$schedule['campaign_id']
							);
			}	
		}

	}
}
	