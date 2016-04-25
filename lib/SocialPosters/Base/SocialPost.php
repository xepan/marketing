<?php

namespace xepan\marketing;

class Model_SocialPosting extends \Model_Table{
	public $table="marketingcampaign_socialpostings";

	function init(){
		parent::init();

		$this->addExpression('social_app')->set(function($m,$q){
			$config = $m->add('xepan/marketing/Model_SocialConfig',array('table_alais'=>'tmp'));
			$user_j = $config->join('xmarketingcampaign_socialusers.config_id');
			$user_j->addField('user_j_id','id');

			$config->addCondition('user_j_id',$q->getField('user_id'));

			return $config->fieldQuery('social_app');

		})->caption('At')->sortable(true);


		$this->hasOne('xepan/marketing/Model_SocialUsers','user_id');
		$this->hasOne('xepan/marketing/SocialPost','post_id');
		
		$this->hasOne('xepan/marketing/Campaign','campaign_id')->sortable(true);

		$this->addField('post_type')->mandatory(true)->sortable(true); // Status Update / Share a link / Group Post etc.

		$this->addField('postid_returned'); // Rturned by social site 
		$this->addField('posted_on')->type('datetime')->defaultValue(date('Y-m-d H:i:s'))->sortable(true);
		$this->addField('group_id')->sortable(true);
		$this->addField('group_name')->sortable(true);

		$this->addField('likes')->sortable(true)->defaultValue(0); // Change Caption in subsequent extended social controller, if nesecorry
		$this->addField('share')->sortable(true)->defaultValue(0); // Change Caption in subsequent extended social controller, if nesecorry
		$this->addExpression('total_comments')->set(function($m,$q){
			return $m->refSQL('xepan/marketing/SocialActivity')->count();
		})->sortable(true);

		$this->addExpression('unread_comments')->set(function($m,$q){
			return $m->refSQL('xepan/marketing/SocialActivity')->addCondition('is_read',false)->count();
		})->sortable(true);

		$this->addField('is_monitoring')->type('boolean')->defaultValue(true)->sortable(true);
		$this->addField('force_monitor')->type('boolean')->defaultValue(false)->caption('Keep Monitoring')->sortable(true);

		$this->hasMany('xepan/marketing/SocialActivity','posting_id');

		$this->add('dynamic_model/Controller_AutoCreator');
	}

	function keep_monitoring(){
		if($this['force_monitor']){
			$this['force_monitor']=false;
			$this['is_monitoring']=false;
		}else{
			$this['force_monitor']=true;
			$this['is_monitoring']=true;
		}
		$this->save();
		return $this;
	}

	function create($user_id, $social_post_id, $postid_returned, $post_type,$group_id=0,$group_name="", $campaign_id=0){
		if($this->loaded()) $this->unload();

		$this['post_type'] = $post_type;
		$this['user_id'] = $user_id;
		$this['post_id'] = $social_post_id;
		$this['postid_returned'] = $postid_returned;
		$this['campaign_id'] = $campaign_id;
		$this['group_id'] = $group_id;
		$this['group_name'] = $group_name;
		$this->save();

		return $this;

	}

	function updateLikesCount($count){
		$this['likes']=$count;
		$this->save();
	}

	function updateShareCount($count){
		$this['share']=$count;
		$this->save();
	}

}

