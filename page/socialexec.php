<?php
namespace xepan\marketing;

class page_socialexec extends \xepan\base\Page{

	function init(){
		parent::init();

		$all_postable_contents = $this->add('xepan/marketing/Model_SocialPost');
		// $all_postable_contents->join('campaing')
		// $post_users = and take its assciated users in group_concat 2,4,6,8 (user_ids)

		foreach ($all_postable_contents as $junk) {

			$social_users = $this->add('xepan/marketing/Model_SocialPosters_Base_SocialUsers');
			// $social_users->addCondition('id',$post_users);

			foreach ($social_users as $junk) {				
				$config = $social_users->ref('config_id');
				$social_app = $config->get('social_app');
				$this->add('xepan/marketing/SocialPosters_'.$social_app)
				->postSingle(
						$social_users,
						$all_postable_contents,
						$config['post_in_groups'], 
						$groups_posted=array(),
						$under_campaign_id=$all_postable_contents['campaign_id']
					);
			}
		}

	}
}
	