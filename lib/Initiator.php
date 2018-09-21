<?php

namespace xepan\marketing;

class Initiator extends \Controller_Addon {
	public $addon_name = 'xepan_marketing';

	function init(){
		parent::init();

		if($_GET['xepan_landing_contact_id'] || $_GET['xepan_landing_campaign_id'] || $_GET['xepan_landing_content_id'] || $_GET['xepan_landing_emailsetting_id'])
			$this->landingResponse();	
	}

	function landingResponse(){	
		if($this->app->page != 'xepan_marketing_unsubscribe'){
			$model_landingresponse = $this->add('xepan\marketing\Model_LandingResponse');
			$model_landingresponse['contact_id'] = $_GET['xepan_landing_contact_id'];
			$model_landingresponse['campaign_id'] = $_GET['xepan_landing_campaign_id'];
			$model_landingresponse['content_id'] = $_GET['xepan_landing_content_id'];
			$model_landingresponse['emailsetting_id'] = $_GET['xepan_landing_emailsetting_id'];
			$model_landingresponse['date'] = $this->app->now;
			$model_landingresponse['type'] = $_GET['source']?:"Unknown";
			$model_landingresponse['referrersite'] = $_GET['xepan_landing_referersite']?:$_SERVER['HTTP_REFERER'];
			$model_landingresponse['ip'] = $_SERVER['REMOTE_ADDR'];

			if($model_landingresponse['referrersite']){
				$model_landingresponse->save();
				$this->app->hook('pointable_event',['landing_response',['lead'=>$model_landingresponse->ref('contact_id'),'response'=>$model_landingresponse]]);											
			}
			
		}	
		
	}

	function setup_admin(){

		$this->routePages('xepan_marketing');
		$this->addLocation(array('template'=>'templates','js'=>'templates/js','css'=>'templates/css'))
		->setBaseURL('../vendor/xepan/marketing/');

		if($this->app->inConfigurationMode)
	            $this->populateConfigurationMenus();
	        else
	            $this->populateApplicationMenus();

		
		
        $this->app->status_icon["xepan\marketing\Model_Lead"] = ['All'=>'fa fa-globe','Active'=>"fa fa-circle text-success",'InActive'=>'fa fa-circle text-danger'];
        $this->app->status_icon["xepan\marketing\Model_Opportunity"] = ['All'=>'fa fa-globe','Open'=>"fa fa-lightbulb-o xepan-effect-yellow",'Converted'=>'fa fa-check text-success','Rejected'=>'fa fa-times text-danger'];
        $this->app->status_icon["xepan\marketing\Model_Newsletter"] = ['All'=>'fa fa-globe','Draft'=>"fa fa-sticky-note-o ",'Submitted'=>'fa fa-check-square-o text-primary','Approved'=>'fa fa-thumbs-up text-success','Rejected'=>'fa fa-times text-danger'];
        $this->app->status_icon["xepan\marketing\Model_SocialPost"] = ['All'=>'fa fa-globe','Draft'=>"fa fa-sticky-note-o ",'Submitted'=>'fa fa-check-square-o text-primary','Approved'=>'fa fa-thumbs-up text-success','Rejected'=>'fa fa-times text-danger'];
        $this->app->status_icon["xepan\marketing\Model_Sms"] = ['All'=>'fa fa-globe','Draft'=>"fa fa-sticky-note-o ",'Submitted'=>'fa fa-check-square-o text-primary','Approved'=>'fa fa-thumbs-up text-success','Rejected'=>'fa fa-times text-danger'];
        $this->app->status_icon["xepan\marketing\Model_Campaign"] = ['All'=>'fa fa-globe','Draft'=>"fa fa-sticky-note-o ",'Submitted'=>'fa fa-check-square-o text-primary','Redesign'=>'fa fa-refresh ','Approved'=>'fa fa-thumbs-up text-success','Onhold'=>'fa fa-pause text-warning'];
		$search_lead = $this->add('xepan\marketing\Model_Lead');
		$this->app->addHook('quick_searched',[$search_lead,'quickSearch']);
		$this->app->addHook('activity_report',[$search_lead,'activityReport']);
		$this->app->addHook('contact_save',[$this,'contactSave']);
		$this->app->addHook('widget_collection',[$this,'exportWidgets']);
		$this->app->addHook('entity_collection',[$this,'exportEntities']);
		$this->app->addHook('collect_shortcuts',[$this,'collect_shortcuts']);

		return $this;
	}

	function populateConfigurationMenus(){
		$m = $this->app->top_menu->addMenu('Marketing');
		$m->addItem(['Social Configuration','icon'=>'fa fa-globe'],$this->app->url('xepan_marketing_socialconfiguration'));
		$m->addItem(['Lead Sources','icon'=>'fa fa-user'],$this->app->url('xepan_marketing_leadsource'));
		$m->addItem(['External Configuration (Via API)','icon'=>'fa fa-gears'],$this->app->url('xepan_marketing_externalconfiguration'));
	}

	function populateApplicationMenus(){
		if(!$this->app->getConfig('hidden_xepan_marketing',false)){
			// $m = $this->app->top_menu->addMenu('Marketing');
			// $m->addItem(['Dashboard','icon'=>'fa fa-dashboard'],'xepan_marketing_dashboard');
			// $m->addItem(['Strategy Planning','icon'=>'fa fa-gavel'],'xepan_marketing_strategyplanning');
			// $m->addItem(['Category Management','icon'=>'fa fa-sitemap'],'xepan_marketing_marketingcategory');
			// $m->addItem(['Lead','icon'=>'fa fa-users'],$this->app->url('xepan_marketing_lead',['status'=>'Active']));
			// $m->addItem(['Lead Assign','icon'=>'fa fa-users'],$this->app->url('xepan_marketing_employeeleadassign'));
			// $m->addItem(['Opportunity','icon'=>'fa fa-user'],$this->api->url('xepan_marketing_opportunity',['watchable'=>true]));
			// $m->addItem(['Newsletter','icon'=>'fa fa-envelope-o'],$this->app->url('xepan_marketing_newsletter',['status'=>'Draft,Submitted,Approved']));
			// $m->addItem(['Social Content','icon'=>'fa fa-globe'],$this->app->url('xepan_marketing_socialcontent',['status'=>'Draft,Submitted,Approved']));
			// $m->addItem(['Tele Marketing','icon'=>'fa fa-phone'],'xepan_marketing_telemarketing');
			// $m->addItem(['SMS','icon'=>'fa fa-envelope-square'],$this->app->url('xepan_marketing_sms',['status'=>'Draft,Submitted,Approved']));
			// $m->addItem(['Campaign','icon'=>'fa fa-bullhorn'],$this->app->url('xepan_marketing_campaign',['status'=>'Draft,Submitted,Redesign,Approved,Onhold']));
			// $m->addItem(['Schedule Timeline','icon'=>'fa fa-bullhorn'],$this->app->url('xepan_marketing_scheduletimeline'));
			// $m->addItem(['Day by Day Analytics','icon'=>'fa fa bar-chart-o'],$this->app->url('xepan_marketing_daybydayanalytics'));
			// $m->addItem(['Reports','icon'=>'fa fa-cog'],'xepan_marketing_report');
			// $m->addItem(['Configuration','icon'=>'fa fa-cog'],'xepan_marketing_socialconfiguration');

			$this->app->user_menu->addItem(['My Sales','icon'=>'fa fa-file-text-o'],$this->app->url('xepan_marketing_mylead'));
			// $this->app->report_menu->addItem(['Employee Lead Report','icon'=>'fa fa-users'],$this->app->url('xepan_marketing_report_employeeleadreport'));
			
		}
	}

	function getConfigTopApplicationMenu(){
		if($this->app->getConfig('hidden_xepan_marketing',false)){return [];}
		
		return ['Marketing_Config'=>[
					[	'name'=>'Social Configuration',
						'icon'=>'fa fa-globe',
						'url'=>'xepan_marketing_socialconfiguration'
					],
					[	'name'=>'Lead Sources',
						'icon'=>'fa fa-user',
						'url'=>'xepan_marketing_leadsource'
					],
					[	'name'=>'External Configuration (Via API)',
						'icon'=>'fa fa-gears',
						'url'=>'xepan_marketing_externalconfiguration'
					]
				]
			];
	}

	function getTopApplicationMenu(){
		if($this->app->getConfig('hidden_xepan_marketing',false)){return [];}

		return ['Marketing'=>[
						[	'name'=>'Strategy Planning',
							'icon'=>'fa fa-gavel',
							'url'=>'xepan_marketing_strategyplanning'
						],
						[	'name'=>'Category Management',
							'icon'=>'fa fa-sitemap',
							'url'=>'xepan_marketing_marketingcategory'
						],
						[	'name'=>'Lead',
							'icon'=>'fa fa-users',
							'url'=>'xepan_marketing_lead',
							'url_param'=>['status'=>'Active']
						],
						[	'name'=>'Lead Assign',
							'icon'=>'fa fa-users',
							'url'=>'xepan_marketing_employeeleadassign'
						],
						[	'name'=>'Opportunity',
							'icon'=>'fa fa-user',
							'url'=>'xepan_marketing_opportunity',
							'url_param'=>['watchable'=>true]
						],
						[	'name'=>'Newsletter',
							'icon'=>'fa fa-envelope-o',
							'url'=>'xepan_marketing_newsletter',
							'url_param'=>['status'=>'Draft,Submitted,Approved']
						],
						[	'name'=>'Social Content',
							'icon'=>'fa fa-globe',
							'url'=>'xepan_marketing_socialcontent',
							'url_param'=>['status'=>'Draft,Submitted,Approved']
						],
						[	'name'=>'Tele Marketing',
							'icon'=>'fa fa-phone',
							'url'=>'xepan_marketing_telemarketing'
						],
						[	'name'=>'Campaign',
							'icon'=>'fa fa-bullhorn',
							'url'=>'xepan_marketing_campaign',
							'url_param'=>['status'=>'Draft,Submitted,Redesign,Approved,Onhold']
						],
						[	'name'=>'Schedule Timeline',
							'icon'=>'fa fa-bullhorn',
							'url'=>'xepan_marketing_scheduletimeline'
						],
						[	'name'=>'Day by Day Analytics',
							'icon'=>'fa fa bar-chart',
							'url'=>'xepan_marketing_daybydayanalytics'
						],
						[	'name'=>'My Sales',
							'icon'=>'fa fa-file-text-o',
							'url'=>'xepan_marketing_mylead',
							'skip_default'=>true
						],
						[	'name'=>'Employee Lead Report',
							'icon'=>'fa fa-users',
							'url'=>'xepan_marketing_report_employeeleadreport',
							'skip_default'=>true
						]
				],
				'Reports'=>[
					[	'name'=>'Employee Lead Report',
						'icon'=>'fa fa-users',
						'url'=>'xepan_marketing_report_employeeleadreport'
					]
				]
			];

		
			// $m->addItem(['Reports','icon'=>'fa fa-cog'],'xepan_marketing_report');
			// $m->addItem(['Configuration','icon'=>'fa fa-cog'],'xepan_marketing_socialconfiguration');
			// $m->addItem(['SMS','icon'=>'fa fa-envelope-square'],$this->app->url('xepan_marketing_sms',['status'=>'Draft,Submitted,Approved']));
	}

	function exportWidgets($app,&$array){
        $array[] = ['xepan\marketing\Widget_DayByDayCommunication','level'=>'Global','title'=>'Employees Day To Day Communication'];
        $array[] = ['xepan\marketing\Widget_LeadAndScore','level'=>'Global','title'=>'Lead Vs Score Chart'];
        $array[] = ['xepan\marketing\Widget_ROI','level'=>'Global','title'=>'Return On Inverstment'];
        $array[] = ['xepan\marketing\Widget_OpportunityPipeline','level'=>'Global','title'=>'Opportunity Pipeline'];
        $array[] = ['xepan\marketing\Widget_EngagementByChannel','level'=>'Global','title'=>'Engagement By Channel'];
        $array[] = ['xepan\marketing\Widget_SaleStaffStatus','level'=>'Global','title'=>'Sales Staff Status'];
        $array[] = ['xepan\marketing\Widget_SaleStaffCommunication','level'=>'Global','title'=>'Sales Staff Communication'];
        $array[] = ['xepan\marketing\Widget_GlobalMassCommunication','level'=>'Global','title'=>'Company Mass Communication Status'];
        $array[] = ['xepan\marketing\Widget_LeadPriority','level'=>'Global','title'=>'Lead Priority'];
        $array[] = ['xepan\marketing\Widget_LeadsAssigned','level'=>'Global','title'=>'Leads Assigned'];
        $array[] = ['xepan\marketing\Widget_LeadsAdded','level'=>'Global','title'=>'Leads Added'];
        $array[] = ['xepan\marketing\Widget_LeadCount','level'=>'Global','title'=>'Lead Count'];
        $array[] = ['xepan\marketing\Widget_SubCommunication','level'=>'Global','title'=>'Sub Communication'];
        $array[] = ['xepan\marketing\Widget_Subscribers','level'=>'Global','title'=>'Online Subscriptions'];
       	
        $array[] = ['xepan\marketing\Widget_DepartmentMassCommunication','level'=>'Department','title'=>'Departmental Mass Communication Status'];
        $array[] = ['xepan\marketing\Widget_DepartmentCommunication','level'=>'Department','title'=>'Departmental Communication Status'];
        $array[] = ['xepan\marketing\Widget_DepartmentSaleStatus','level'=>'Department','title'=>'Department Sale Status'];
        $array[] = ['xepan\marketing\Widget_DepartmentLeadsAdded','level'=>'Department','title'=>'Department Leads Added'];
        $array[] = ['xepan\marketing\Widget_DepartmentLeadsAssigned','level'=>'Department','title'=>'Department Leads Assigned'];
        
        $array[] = ['xepan\marketing\Widget_MyCommunication','level'=>'Individual','title'=>'My Communication Graph'];
        $array[] = ['xepan\marketing\Widget_MySaleStatus','level'=>'Individual','title'=>'My Sales Status'];
        $array[] = ['xepan\marketing\Widget_MyDayByDayCommunication','level'=>'Individual','title'=>'My Day To Day Communication'];
        $array[] = ['xepan\marketing\Widget_MyMassCommunication','level'=>'Individual','title'=>'My Mass Communication Status'];
    }

     function exportEntities($app,&$array){
        $array['Lead'] = ['caption'=>'Lead','type'=>'xepan\base\Basic','model'=>'xepan\marketing\Model_Lead'];
        $array['Opportunity'] = ['caption'=>'Opportunity','type'=>'xepan\base\Basic','model'=>'xepan\marketing\Model_Opportunity'];
        $array['MarketingCategory'] = ['caption'=>'MarketingCategory','type'=>'xepan\base\Basic','model'=>'xepan\marketing\Model_MarketingCategory'];
        $array['Campaign'] = ['caption'=>'Campaign','type'=>'xepan\base\Basic','model'=>'xepan\marketing\Model_Campaign'];
        $array['Newsletter'] = ['caption'=>'Newsletter','type'=>'xepan\base\Basic','model'=>'xepan\marketing\Model_Newsletter'];
        $array['SocialPost'] = ['caption'=>'SocialPost','type'=>'xepan\base\Basic','model'=>'xepan\marketing\Model_SocialPost'];
        $array['socialPosting'] = ['caption'=>'socialPosting','type'=>'xepan\base\Basic','model'=>'xepan\marketing\Model_socialPosting'];
        $array['TeleCommunication'] = ['caption'=>'TeleCommunication','type'=>'xepan\base\Basic','model'=>'xepan\marketing\Model_TeleCommunication'];
        $array['Sms'] = ['caption'=>'Sms','type'=>'xepan\base\Basic','model'=>'xepan\marketing\Model_Sms'];
        // $array['OutsourceParty'] = ['caption'=>'OutsourceParty','type'=>'xepan\base\Basic','model'=>'xepan\marketing\Model_OutsourceParty'];
        $array['SocialPosters_Facebook_FacebookConfig'] = ['caption'=>'SocialPosters_Facebook_FacebookConfig','type'=>'xepan\base\Basic','model'=>'xepan\marketing\Model_SocialPosters_Facebook_FacebookConfig'];
        $array['SocialPosters_Base_SocialConfig'] = ['caption'=>'SocialPosters_Base_SocialConfig','type'=>'xepan\base\Basic','model'=>'xepan\marketing\Model_SocialPosters_Base_SocialConfig'];
        $array['SocialPosters_Linkedin_LinkedinConfig'] = ['caption'=>'SocialPosters_Linkedin_LinkedinConfig','type'=>'xepan\base\Basic','model'=>'xepan\marketing\Model_SocialPosters_Linkedin_LinkedinConfig'];
        $array['ORGANIZATIONS_STRATEGY_PLANNING'] = ['caption'=>'ORGANIZATIONS_STRATEGY_PLANNING','type'=>'xepan\base\Basic','model'=>'xepan\marketing\Model_ORGANIZATIONS_STRATEGY_PLANNING'];
        $array['MARKETING_LEAD_SOURCE'] = ['caption'=>'MARKETING_LEAD_SOURCE','type'=>'xepan\base\Basic','model'=>'xepan\marketing\Model_Config_LeadSource'];

    }

    function collect_shortcuts($app,&$shortcuts){
		// $shortcuts[]=["title"=>"New Email","keywords"=>"new email send","description"=>"Send New Email","normal_access"=>"My Menu -> Tasks / New Task Button","url"=>$this->app->url('xepan/projects/mytasks',['admin_layout_cube_mytasks_virtualpage'=>'true']),'mode'=>'frame'];
		$shortcuts[]=["title"=>"Strategy Planning","keywords"=>"Strategy Planning company target audiance location competitors description","description"=>"Define your companies basic stratagy","normal_access"=>"Marketing -> Strategy Planning","url"=>$this->app->url('xepan_marketing_strategyplanning'),'mode'=>'frame'];
		$shortcuts[]=["title"=>"Marketing Category","keywords"=>"marketing category sections segments areas","description"=>"Define your companies marketing categories","normal_access"=>"Marketing -> Category Management","url"=>$this->app->url('xepan_marketing_marketingcategory'),'mode'=>'frame'];
		$shortcuts[]=["title"=>"Lead Management","keywords"=>"lead all contacts search person customer vendor opportunity","description"=>"Manage your companies leads","normal_access"=>"Marketing -> Lead","url"=>$this->app->url('xepan_marketing_lead'),'mode'=>'frame'];
		$shortcuts[]=["title"=>"Assign Multiple Leads","keywords"=>"lead assign employee staff","description"=>"Assign lead to employee","normal_access"=>"Marketing -> Lead Assign","url"=>$this->app->url('xepan_marketing_employeeleadassign'),'mode'=>'frame'];
		$shortcuts[]=["title"=>"Opportunities","keywords"=>"lead opportunities","description"=>"Manage Business Opportunities","normal_access"=>"Marketing -> Opportunity","url"=>$this->app->url('xepan_marketing_opportunity',['watchable'=>1]),'mode'=>'frame'];
		$shortcuts[]=["title"=>"Newsletters","keywords"=>"newsletters news letter email template","description"=>"Manage Newsletters","normal_access"=>"Marketing -> Newsletter","url"=>$this->app->url('xepan_marketing_newsletter'),'mode'=>'fullframe'];
		$shortcuts[]=["title"=>"Social Content","keywords"=>"social content post facebook blog","description"=>"Manage Newsletters","normal_access"=>"Marketing -> Social Content","url"=>$this->app->url('xepan_marketing_socialcontent',['status'=>'Draft,Submitted,Approved']),'mode'=>'frame'];
		$shortcuts[]=["title"=>"Tele Marketing","keywords"=>"tele marketing stanard view","description"=>"Tele Marketing Standard View","normal_access"=>"Marketing -> Tele Marketing","url"=>$this->app->url('xepan_marketing_telemarketing'),'mode'=>'fullframe'];
		$shortcuts[]=["title"=>"Tele Marketing List View","keywords"=>"tele marketing list view","description"=>"Tele Marketing List View","normal_access"=>"Marketing -> Tele Marketing / List View","url"=>$this->app->url('xepan_marketing_telemarketinglistview'),'mode'=>'fullframe'];
		$shortcuts[]=["title"=>"SMS Content","keywords"=>"sms marketing content","description"=>"SMS Content Management","normal_access"=>"Marketing -> SMS","url"=>$this->app->url('xepan_marketing_sms',['status'=>'Draft,Submitted,Approved']),'mode'=>'frame'];
		$shortcuts[]=["title"=>"Marketing Campaigns","keywords"=>"campaigns marketing schedule newsletter day date ","description"=>"Manage Marketing Campaigns","normal_access"=>"Marketing -> Campaign","url"=>$this->app->url('xepan_marketing_campaign',['status'=>'Draft,Submitted,Redesign,Approved,Onhold']),'mode'=>'frame'];
		$shortcuts[]=["title"=>"Campaigns Scheduled Timeline","keywords"=>"campaigns marketing schedule timeline","description"=>"Monitor your scheduled marketing campaigns","normal_access"=>"Marketing -> Schedule Timeline","url"=>$this->app->url('xepan_marketing_scheduletimeline'),'mode'=>'frame'];
		$shortcuts[]=["title"=>"Analytics - Marketing Day by Day","keywords"=>"marketing day by day analysis","description"=>"Marketing day by day analysis","normal_access"=>"Marketing -> Day by Day Analytics","url"=>$this->app->url('xepan_marketing_daybydayanalytics'),'mode'=>'frame'];
		$shortcuts[]=["title"=>"Social Configuration","keywords"=>"facebook google blogger linkedin app","description"=>"Manage Social integration configurations with FB/Google Blogger/Linkedin","normal_access"=>"Marketing -> Configuration","url"=>$this->app->url('xepan_marketing_socialconfiguration'),'mode'=>'frame'];
		$shortcuts[]=["title"=>"Lead Source","keywords"=>"lead source","description"=>"Configure lead sources","normal_access"=>"Marketing -> Configuration/ SideBar -> Lead Source","url"=>$this->app->url('xepan_marketing_leadsource'),'mode'=>'frame'];
		$shortcuts[]=["title"=>"Lead External Configuration","keywords"=>"lead external information api","description"=>"Get external inforamtion about lead","normal_access"=>"Marketing -> Configuration/ SideBar -> External Configuration","url"=>$this->app->url('xepan_marketing_externalconfiguration'),'mode'=>'frame'];
	}
	
	function contactSave($app,$m){
		if($m->id == $this->app->employee->id)
			return;
		
		// finding id of marketing category
		$marketing_category = $this->add('xepan\marketing\Model_MarketingCategory');
		$marketing_category->tryLoadBy('name',$m['status'].' '.$m['type']);

		if(!$marketing_category->loaded())
			return;

		$active_category = $this->add('xepan\marketing\Model_MarketingCategory');
		$active_category->tryLoadBy('name','Active '.$m['type']);
		$active_cat_id = $active_category->id;  
		
		$inactive_category = $this->add('xepan\marketing\Model_MarketingCategory');
		$inactive_category->tryLoadBy('name','InActive '.$m['type']);
		$inactive_cat_id = $inactive_category->id;


		// finding current association 
		$cat_assoc = $this->add('xepan\marketing\Model_Lead_category_Association');
		$cat_assoc->addCondition('lead_id',$m->id);
		$cat_assoc->addCondition('marketing_category_id',[$active_cat_id,$inactive_cat_id]);

		// deleting current association 
		if($cat_assoc->count()->getOne() > 0){									
			foreach ($cat_assoc as $association_row){
				$association_row->delete();
			}
		}

		// new association 		
		$category_association_m = $this->add('xepan\marketing\Model_Lead_category_Association');
		$category_association_m['lead_id'] = $m->id;
		$category_association_m['marketing_category_id'] = $marketing_category->id;
		$category_association_m->save();		
	}

	function setup_frontend(){
		$this->routePages('xepan_marketing');
		$this->addLocation(array('template'=>'templates','js'=>'templates/js'))
		->setBaseURL('./vendor/xepan/marketing/');

		$this->app->addHook('cron_executor',function($app){
			
			$now = \DateTime::createFromFormat('Y-m-d H:i:s', $this->app->now);
			echo "Testing in Marketing<br/>";
			var_dump($now);

			$job1 = new \Cron\Job\ShellJob();
			$job1->setSchedule(new \Cron\Schedule\CrontabSchedule('0 0 * * *')); // every midnight
			if(!$job1->getSchedule() || $job1->getSchedule()->valid($now)){	
				echo " Updating last_communication_before_days++ at midnight <br/>";
				$this->app->db->dsql()->expr('update contact set last_communication_before_days = last_communication_before_days+1')->execute();
			}

			$job2 = new \Cron\Job\ShellJob();
			$job2->setSchedule(new \Cron\Schedule\CrontabSchedule('* * * * *'));
			if(!$job2->getSchedule() || $job2->getSchedule()->valid($now)){	
				echo " Executing Newsletter exec <br/>";
				$this->add('xepan\marketing\Controller_NewsLetterExec');
			}

			$job3 = new \Cron\Job\ShellJob();
			$job3->setSchedule(new \Cron\Schedule\CrontabSchedule('* * * * *'));
			if(!$job3->getSchedule() || $job3->getSchedule()->valid($now)){	
				echo " Executing Social exec <br/>";
				$this->add('xepan\marketing\Controller_SocialExec');
			}

			$job4 = new \Cron\Job\ShellJob();
			$job4->setSchedule(new \Cron\Schedule\CrontabSchedule('7 * * * *')); // every hour and 7 minutes
			if(!$job4->getSchedule() || $job4->getSchedule()->valid($now)){	
				echo " Executing API Fetch <br/>";
				$dir = new \DirectoryIterator('vendor/xepan/marketing/lib/Controller/APIConnector/');
				foreach ($dir as $fileinfo) {
				    if (!$fileinfo->isDot()) {
				    	$api = $fileinfo->getFilename();
				    	$api = str_replace(".php", '', $api);
				        $cont = $this->add('xepan\marketing\Controller_APIConnector_'.$api);
				        $cont->execute();
				    }
				}
			}

		});

		if($this->app->isEditing){
			// deprecated bacause custom can be used as subscription tool
			// $this->app->exportFrontEndTool('xepan\marketing\Tool_Subscription','Marketing');
		}

		return $this;
	}

	function resetDB(){
		$category_name = ['Default',
					 'Active Affiliate',
					 'InActive Affiliate',
					 'Active Employee',
					 'InActive Employee',
					 'Active Customer',
					 'InActive Customer',
					 'Active Supplier',
					 'InActive Supplier',
					 'Active OutSourceParty',
					 'InActive OutSourceParty'
					];

       	
       	foreach ($category_name as $cat) {
        	$mar_cat=$this->add('xepan\marketing\Model_MarketingCategory');
        	$mar_cat['name'] = $cat;
        	$mar_cat['system'] = true;
        	$mar_cat->save(); 
       	}

        $news=$this->add('xepan\marketing\Model_Newsletter');
        $news['marketing_category_id']=$mar_cat->id;
        $news['message_160']="No Content";
        $news['message_255']="No Content";
        $news['message_3000']="No Content";
        $news['message_blog']="No Content";
        $news['url']="xavoc.com";
        $news['title']="Empty";
        $news['is_template']=true;

        $news->save();
        // Create default Company Department
	}
}
