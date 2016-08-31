<?php

namespace xepan\marketing;
	
class page_lead extends \xepan\base\Page{
	public $title = "Lead";
	public $content=null;
	function page_index(){

		$lead = $this->add('xepan\marketing\Model_Lead');

		if($status = $this->app->stickyGET('status'))
			$lead->addCondition('status',$status);
		$lead->add('xepan\marketing\Controller_SideBarStatusFilter');
		$lead->setOrder('total_visitor','desc');
		$crud = $this->add('xepan\hr\CRUD',['action_page'=>'xepan_marketing_leaddetails'],null,['grid/lead-grid']);
		$crud->setModel($lead)->setOrder('created_at','desc');	
		$crud->grid->addPaginator(50);
		$crud->add('xepan\base\Controller_Avatar');
		
		$frm=$crud->grid->addQuickSearch(['name','website','contacts_str']);
				
		$status=$frm->addField('Dropdown','marketing_category_id')->setEmptyText('Categories');
		$status->setModel('xepan\marketing\MarketingCategory');

		$frm->addHook('applyFilter',function($f,$m){
			if($f['marketing_category_id']){
				$cat_asso = $this->add('xepan\marketing\Model_Lead_Category_Association');
				$cat_asso->addCondition('marketing_category_id',$f['marketing_category_id']);
				$m->addCondition('id','in',$cat_asso->fieldQuery('lead_id'));
			}
		});
		
		$status->js('change',$frm->js()->submit());

		$crud->grid->addColumn('category');
		$crud->grid->addMethod('format_marketingcategory',function($grid,$field){				
				$data = $grid->add('xepan\marketing\Model_Lead_Category_Association')->addCondition('lead_id',$grid->model->id);
				$l = $grid->add('Lister',null,'category',['grid/lead-grid','category_lister']);
				$l->setModel($data);
				
				$grid->current_row_html[$field] = $l->getHtml();
		});

		$crud->grid->addFormatter('category','marketingcategory');
		$crud->grid->js(true)->_load('jquery.sparkline.min')->_selector('.sparkline')->sparkline('html', ['enableTagOptions' => true]);
		if(!$crud->isEditing()){
			$crud->grid->js('click')->_selector('.do-view-lead')->univ()->frameURL('Lead Details',[$this->api->url('xepan_marketing_leaddetails'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
			$crud->grid->js('click')->_selector('.do-view-lead-visitor')->univ()->frameURL('Total Visits',[$this->api->url('xepan_marketing_leadvisitor'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
			$crud->grid->js('click')->_selector('.do-view-lead-score')->univ()->frameURL('Total Score',[$this->api->url('xepan_marketing_leadscore'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
		}

		$btn = $crud->grid->addButton('Grab')->addClass('btn btn-primary');
		$btn->js('click',$this->js()->univ()->frameURL('Data Grabber',$this->app->url('./grab')));

	}

	function page_grab(){
		// echo "TYpe of code : Google serach result page, website, portal, yahoo search result page, bing search result page <br/>";
		// echo "Selector for urls <br/>";
		// echo "Code Block :: text area <br/>";
		$array	=
				[
					''=>'Please Select',
					'Google Search Result Page'=>'Google Search Result Page',
					'Website Page'=>'Website Page',
					'Yahoo Search Result Page'=>'Yahoo Search Result Page',
					'Portal'=>'Portal',
					'Other'=>'Other'
				];
		$pages_selector = $this->app->stickyGET('type_of_pages');		
		$f=$this->add('Form');
		$tyop = $f->addField('DropDown','type_of_pages')->setValueList($array);
		$url_select = $f->addField('line','url_selector')->set($pages_selector)->validate('required');
		$f->addField('text','html_code');

		$f->addSubmit('Grab');
		// $tyop->js('change',$url_select->js()->reload(null,null,[$this->app->url(null,['cut_object'=>$url_select->name]),'type_of_pages'=>$tyop->js()->val()]));
		$tyop->js('change',$f->js()->atk4_form('reloadField','url_selector',[$this->app->url(null,['cut_object'=>$url_select->name]),'type_of_pages'=>$tyop->js()->val()]));
		// $tyop->js('change',$url_select->js()->reload(['type_of_pages'=>$tyop->js()->val()]));
	
		if($f->isSubmitted()){
			$crawler = new  \Symfony\Component\DomCrawler\Crawler($f['html_code']);
			$url_array=[];
			$crawler->filter('h3 > a')->each(function($node,$i)use(&$url_array){
				 $url_array[] = $node->attr('href');
			});

			$client = new \GuzzleHttp\Client();

			foreach ($url_array as $url) {
				$this->createLeads($this->getEmails($url),$url);

				/*Search Links Contening Contact Fetch Page & Get links From Page & create Lead*/
				$crawler = new  \Symfony\Component\DomCrawler\Crawler((string)$this->content);
				$web_url=[];
				$crawler->links()->each(function($node,$i)use(&$web_url){
					$temp=$node->attr('href');
					if(strpos($temp, 'contact')!==false OR strpos($temp, 'about')!==false ) 
						$web_url[] = $temp->getUri();
				});
				
				foreach ($web_url as $web_url) {
					$this->createLeads($this->getEmails($web_url),$web_url);
				}
			}

			// $f->js()->reload()->execute();
		}

	}

	function getEmails($url){
		$client = new \GuzzleHttp\Client();
		$email_return=[];
		try{
			$res = $client->request('GET',$url);
			$this->content = $content = $res->getBody();
			/*Get Emails From This Page & Create Lead*/
			$pattern = '/[a-z0-9_\-\+\.]+(@|(.)?\[(.)?at(.)?\](.)?)[a-z0-9\-]+(\.|(.)?\[(.)?dot(.)?\](.)?)([a-z]{2,3})(?:(\.|(.)?\[(.)?dot(.)?\](.)?)[a-z]{2})?/i';
			$pattern = '/[a-z0-9_\-\+\.]{1,80}+@[a-z0-9\-]{1,80}+\.([a-z]{2,3})(?:\.[a-z]{2})?/i';
			// preg_match_all returns an associative array
			preg_match_all($pattern, (string)$content, $email_found);
			// print_r($email_found);
			foreach ($email_found[0] as $em) {
				$email_return[] = $em;
			}		
		}catch(\GuzzleHttp\Exception\RequestException $e){
			
		}
		// print_r($email_return);
		return $email_return;
	}

	function createLeads($emails,$url){
		// var_dump($emails);
		echo "<pre>";
		echo $url;
		print_r($emails);
		echo "</pre>";
		return;
		// foreach ($emails as  $email) {
		// 	$existing_email=$this->add('xepan\base\Model_Contact_Email');
		// 	$existing_email->addCondition('value',$email);
		// 	$existing_email->tryLoadAny();
		// 	try{
		// 		if(!$existing_email->loaded()){
		// 			$lead=$this->add('xepan\marketing\Model_Lead');
		// 			$lead['first_name'] = "Grab";
		// 			$lead['last_name'] = "Lead";
		// 			$lead['website'] = $url;
		// 			$lead->save();

		// 			$email_info=$this->add('xepan\base\Model_Contact_Email');
		// 			$email_info['contact_id']=$lead->id;
		// 			$email_info['head']="Official";
		// 			$email_info['value'] = $email;
		// 			$email_info->save();
		// 		}
		// 	}catch(\Exception $e){
		// 		// echo $email;
		// 	}			
		// }
	}
}