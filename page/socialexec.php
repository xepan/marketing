<?php
namespace xepan\marketing;

class page_socialexec extends \xepan\base\Page{

	function init(){
		parent::init();

		$all_postable_contents = $this->add('xepan/marketing/Model_SocialPost');
		$all_postable_contents->addExpression('first_image')->set(function($m,$q){
			return $m->refSQL('Attachments')->setLimit(1)->fieldQuery('file');
		});

		$schedule_j = $all_postable_contents->join('schedule.document_id','id');
		$schedule_j->addField('posted_on');
		$schedule_j->addField('schedule_campaign_id','campaign_id');

		$campaign_j = $schedule_j->join('campaign.document_id','campaign_id');
		$campain_document_j = $campaign_j->join('document','document_id');
		$campain_document_j->addField('campaign_status','status');
		$campaign_j->addField('ending_date');

		$all_postable_contents->addCondition('status','Approved');
		$all_postable_contents->addCondition('campaign_status','Approved');
		$all_postable_contents->addCondition('ending_date','>=',$this->app->today);
		$all_postable_contents->addCondition('posted_on',null);
		
		// $all_postable_contents->addExpression('socialUsers')->set(function($m,$q){
		// 	$x = $m->add('xepan\marketing\Model_Campaign_SocialUser_Association',['table_alias'=>'users_str']);
		// 	return $x->addCondition('campaign_id',$q->getField('campaign_id'))->_dsql()->del('fields')->field($q->expr('group_concat([0] SEPARATOR ",")',[$x->getElement('')]));
		// });

		// $social_post_array = ['Facebook'=>['user_id','user_obj'=>'user_object','post_id'=>11,'post_obj'=>'post_model']];
		$social_post_array = [];
		foreach ($all_postable_contents as $postable_content) {
			$asso_users = $this->add('xepan\marketing\Model_Campaign_SocialUser_Association')
						->addCondition('campaign_id',$postable_content['schedule_campaign_id'])
						->addCondition('is_active',true)
						;

			foreach ($asso_users as $asso_user) {
				if(!isset($social_post_array[$asso_user['type']])){
					$social_post_array[$asso_user['type']] = [];
					$temp_array = [];
				}

				if($social_post_array[$asso_user['type']]['user_id'] === $asso_user['socialuser_id'] and $social_post_array[$asso_user['type']]['post_id'] === $postable_content['id'])
					continue;

				$temp_array['user_id'] = $asso_user['socialuser_id'];
				$temp_array['user_obj'] = $social_user = $this->add('xepan\marketing\Model_SocialUser')->load($asso_user['socialuser_id']);
				$temp_array['config_obj'] = $this->add('xepan\marketing\Model_SocialPosters_Base_SocialConfig')->load($social_user['config_id']);
				$temp_array['post_id'] = $postable_content['id'];
				$temp_array['post_obj'] = $this->add('xepan\marketing\Model_SocialPost')->load($postable_content['id']);

				array_push($social_post_array[$asso_user['type']], $temp_array);
			}
		}

		foreach ($social_post_array as $social_app => $value) {
				$this->add('xepan/marketing/SocialPosters_'.$social_app)
					->postAll($value);
			}
		}
	}
	