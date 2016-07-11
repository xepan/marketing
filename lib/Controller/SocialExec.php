<?php
namespace xepan\marketing;

class Controller_SocialExec extends \AbstractController{

	function init(){
		parent::init();

		$all_postable_contents = $this->add('xepan/marketing/Model_SocialPost');

		$schedule_j = $all_postable_contents->join('schedule.document_id','id');
		$schedule_j->addField('posted_on');
		$schedule_j->addField('schedule_campaign_id','campaign_id');
		$schedule_j->addField('schedule_id','id');
		$schedule_j->addField('scheduled_datetime','date');

		$campaign_j = $schedule_j->join('campaign.document_id','campaign_id');
		$campain_document_j = $campaign_j->join('document','document_id');
		$campain_document_j->addField('campaign_status','status');
		$campaign_j->addField('ending_date');

		$all_postable_contents->addCondition('status','Approved');
		$all_postable_contents->addCondition('campaign_status','Approved');
		$all_postable_contents->addCondition('ending_date','>=',$this->app->today);
		$all_postable_contents->addCondition('scheduled_datetime','<=',$this->app->now);
		$all_postable_contents->addCondition('posted_on',null);

		
		// $social_post_array = ['Facebook'=>['user_id','user_obj'=>'user_object','post_id'=>11,'post_obj'=>'post_model']];
		$social_post_array = [];
		foreach ($all_postable_contents as $postable_content) {
			$asso_users = $this->add('xepan\marketing\Model_Campaign_SocialUser_Association')
						->addCondition('campaign_id',$postable_content['schedule_campaign_id'])
						->addCondition('is_active',true)
						;
			// echo $postable_content['schedule_campaign_id']."<br/>";
			// echo $asso_users->count()->getOne()."<br/>";
			// continue;

			foreach ($asso_users as $asso_user) {
				if(!isset($social_post_array[$asso_user['type']])){
					$social_post_array[$asso_user['type']] = [];
					$temp_array = [];
				}

				if($social_post_array[$asso_user['type']]['user_id'] === $asso_user['socialuser_id'] and $social_post_array[$asso_user['type']]['post_id'] === $postable_content['id'])
					continue;
					
				$post_model = $this->add('xepan\marketing\Model_SocialPost')->load($postable_content['id']);
				
				// APPENDING VALUES IN URL
				$pq = new \xepan\cms\phpQuery();
				$dom = $pq->newDocument($post_model['message_blog']);
				
				foreach ($dom['a'] as $anchor){
					$a = $pq->pq($anchor);
					$url = $this->app->url($a->attr('href'),['action'=>null,'document_id'=>null,'xepan_landing_campaign_id'=>$postable_content['schedule_campaign_id'],'xepan_landing_content_id'=>$postable_content['id']])->absolute()->getURL();
					$a->attr('href',$url);
				}
				$post_model['message_blog'] = $dom->html();

				if($post_model['url']){
					$url = $this->app->url($post_model['url'],['xepan_landing_campaign_id'=>$postable_content['schedule_campaign_id'],'xepan_landing_content_id'=>$postable_content['id'],'source'=>'Social'])->absolute()->getURL();
					$post_model['url'] = $url;
				}

				$post_image_url = (string)$post_model->ref('Attachments')->setLimit(1)->fieldQuery('file');
				$post_image_path = "";
				if($post_image_url){
					$post_image_path = $_SERVER['DOCUMENT_ROOT'].$post_image_url;
					if(!file_exists($post_image_path))
						$post_image_path = "";
				}

				$temp_array['user_id'] = $asso_user['socialuser_id'];
				$temp_array['user_obj'] = $social_user = $this->add('xepan\marketing\Model_SocialUser')->load($asso_user['socialuser_id']);
				$temp_array['config_obj'] = $this->add('xepan\marketing\Model_SocialPosters_Base_SocialConfig')->load($social_user['config_id']);
				$temp_array['post_id'] = $postable_content['id'];
				$temp_array['post_obj'] = $post_model;
				$temp_array['post_image_path'] = $post_image_path;
				$temp_array['campaign_id'] = $postable_content['schedule_campaign_id'];
				$temp_array['schedule_id'] = $postable_content['schedule_id'];												
				
				$social_post_array[$asso_user['type']][] = $temp_array;

			}

		}

		foreach ($social_post_array as $social_app => $value) {				
				$this->add('xepan/marketing/SocialPosters_'.$social_app)
					->postAll($value);
		}
		echo $all_postable_contents->count()->getOne(). ' Posts done' ;
		}
	}
	