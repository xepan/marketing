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
		set_time_limit(0);
		// echo "TYpe of code : Google serach result page, website, portal, yahoo search result page, bing search result page <br/>";
		// echo "Selector for urls <br/>";
		// echo "Code Block :: text area <br/>";
		$array	=
				[
					''=>'Please Select',
					'h3 a'=>'Google Search Result Page',
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
		$category_field = $f->addField('Dropdown','categories');
		$category_field->setModel('xepan\marketing\Model_MarketingCategory');
		$category_field->setAttr(['multiple'=>'multiple']);

		$f->addSubmit('Grab');
		// $tyop->js('change',$url_select->js()->reload(null,null,[$this->app->url(null,['cut_object'=>$url_select->name]),'type_of_pages'=>$tyop->js()->val()]));
		$tyop->js('change',$f->js()->atk4_form('reloadField','url_selector',[$this->app->url(null,['cut_object'=>$url_select->name]),'type_of_pages'=>$tyop->js()->val()]));
		// $tyop->js('change',$url_select->js()->reload(['type_of_pages'=>$tyop->js()->val()]));
	
		if($f->isSubmitted()){
			$category = $f['categories'];			

			$this->grab('http://searchpage.null/root',$f['html_code'],$f['url_selector']);
			$unique_emails=[];
			foreach ($this->grabbed_data as $host => $pages) {
				
				if(!isset($unique_emails[$host])){
					$unique_emails[$host]=[];
				}

				foreach ($pages as $page => $emails) {
					foreach ($emails as $email) {
						if(!in_array($email, $unique_emails[$host]))
							$unique_emails[$host][] = $email;
					}
				}
			}

			foreach ($unique_emails as $host => $emails) {
				$this->createLeads($emails,$host,$category);
			}

		}

	}

	function grab($url, $content, $regex_selector /*, $max_page_depth, $max_domain_depth, $total_max_page_depth, $initial_domain_depth, $path*/){
		
		try{
		
			$parsed_url = parse_url($url);

			$start=microtime(true);
			// get Emails and Mobile Number and ... 
			$pattern = '/[a-z0-9_\-\+\.]+(@|(.)?\[(.)?at(.)?\](.)?)[a-z0-9\-]+(\.|(.)?\[(.)?dot(.)?\](.)?)([a-z]{2,3})(?:(\.|(.)?\[(.)?dot(.)?\](.)?)[a-z]{2})?/i';
			$pattern = '/[a-z0-9_\-\+\.]{1,80}+@[a-z0-9\-]{1,80}+\.([a-z]{2,3})(?:\.[a-z]{2})?/i';
			// preg_match_all returns an associative array
			preg_match_all($pattern, $content, $email_found);
			// echo '<br/>'.$path . " [<b> $url </b>] @ <b>$max_page_depth</b> level". "<br/>";
			$end=microtime(true);
			// echo print_r($email_found[0],true) . ' in '.($end-$start).' seconds from <b>'.$url.'</b><br/>';
			// ob_flush();
			// flush();

			$this->grabbed_data[$parsed_url['host']][$parsed_url['path'] . $parsed_url['query']] = $email_found[0];

			$pq = new phpQuery();
			$doc = @$pq->newDocumentHTML($content);
			
			// if($max_domain_depth== $initial_domain_depth)
				$get_a = $doc[$regex_selector];
			// else
				// $get_a = $doc['a:contains("contact")'];

			// echo "Found Links: ";
			
			$unique_filtered_links = array();

			foreach ($get_a as $a) {
				// echo '<br/>--------  &nbsp; &nbsp; &nbsp; '.$pq->pq($a)->attr('href'). ' <br/>';
				preg_match('/(\.pdf|\.exe|\.msi|\.zip|\.rar|\.gz|\.tar|\.flv|\.mov|\.mpg|\.mpeg)/i', $pq->pq($a)->attr('href'),$arr);
				if(count($arr)) {
					// echo "Found pdf etc so not taking to check in ". $pq->pq($a)->attr('href') .'<br/>';
					continue;
				}


				$new_website = parse_url($pq->pq($a)->attr('href'));
				if(!$new_website['scheme']) $new_website['scheme'] = $parsed_url['scheme'];
				if(!$new_website['host']) $new_website['host'] = $parsed_url['host'];
				$new_url = $new_website['scheme'].'://'.$new_website['host'] . '/'.$new_website['path'].$new_website['query'];

				// if(in_array($new_website['path'].$new_website['query'], array_keys($this->grabbed_data[$parsed_url['host']]))){
				// 	echo "Already Visited <br/>";
				// 	continue;
				// }

				if(!in_array($new_url, $unique_filtered_links)){
					$unique_filtered_links[] = $new_url;
				}
			}
			// echo "Unique Links to check <br/>";
			// print_r($unique_filtered_links);

			$start = microtime(true);
			$results = $this->multi_request($unique_filtered_links);
			// ==================== 
			// echo "Fetched ". count($unique_filtered_links).  " websites in ". (microtime(true) - $start) . ' seconds <br/>';

			$contact_us_pages =array();
			foreach ($unique_filtered_links as $id => $site_url) {
				// somehow if no result was found just carry on
				if(!$results[$id]) {
					// echo "No Result for " . $site_url. '<br/>';
					continue;
				}

				$parsed_url = parse_url($site_url);

				preg_match_all($pattern, $results[$id], $email_found);
				$this->grabbed_data[$parsed_url['host']][$parsed_url['path'] . $parsed_url['query']] = $email_found[0];

				
				$doc = @$pq->newDocumentHTML($results[$id]);
				$get_a = $doc['a:contains("contact")'];

				foreach ($get_a as $a) {
					// echo '<br/>--------  &nbsp; &nbsp; &nbsp; '.$pq->pq($a)->attr('href'). ' <br/>';
					preg_match('/(\.pdf|\.exe|\.msi|\.zip|\.rar|\.gz|\.tar|\.flv|\.mov|\.mpg|\.mpeg)/i', $pq->pq($a)->attr('href'),$arr);
					if(count($arr)) {
						// echo "Found pdf etc so not taking to check in ". $pq->pq($a)->attr('href') .'<br/>';
						continue;
					}


					$new_website = parse_url($pq->pq($a)->attr('href'));
					if(!$new_website['scheme']) $new_website['scheme'] = $parsed_url['scheme'];
					if(!$new_website['host']) $new_website['host'] = $parsed_url['host'];
					$new_url = $new_website['scheme'].'://'.$new_website['host'] . '/'.$new_website['path'].$new_website['query'];

					// if(in_array($new_website['path'].$new_website['query'], array_keys(is_array($this->grabbed_data[$parsed_url['host']])?:array()))){
					// 	echo "Already Visited <br/>";
					// 	continue;
					// }

					if(!in_array($new_url, $contact_us_pages)){
						$contact_us_pages[] = $new_url;
					}
				}
			}
			
			// echo "Unique Contact Links to check <br/>";
			// print_r($contact_us_pages);

			$start = microtime(true);
			$results = $this->multi_request($contact_us_pages);
			
			// ====================
			// echo "Fetched ". count($contact_us_pages).  " contact-pages in ". (microtime(true) - $start) . ' seconds <br/>';

			foreach ($results as $id => $contact_page_content) {
				if(!$results[$id]){
					// echo "Contact Page no result ". $contact_us_pages[$id] .'<br/>';
					continue;
				}

				$parsed_url = parse_url($contact_us_pages[$id]);

				preg_match_all($pattern, $contact_page_content, $email_found);
				$this->grabbed_data[$parsed_url['host']][$parsed_url['path'] . $parsed_url['query']] = $email_found[0];
			}

		}catch(Exception $e){
			return;
		}
	}

	function multi_request($urls)
	{
		$curly = array();
		$result = array();
		$mh = curl_multi_init();

		foreach ($urls as $id => $url) {
			$curly[$id] = curl_init();
			curl_setopt($curly[$id], CURLOPT_URL, $url);
			curl_setopt($curly[$id], CURLOPT_HEADER, 0);
			curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curly[$id], CURLOPT_TIMEOUT, 30);
			curl_setopt($curly[$id], CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curly[$id], CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curly[$id], CURLOPT_SSL_VERIFYHOST, 0);
			curl_multi_add_handle($mh, $curly[$id]);
		}

		$running = null;
		do {
			curl_multi_exec($mh, $running);
		} while($running > 0);

		foreach($curly as $id => $c) {
			$result[$id] = curl_multi_getcontent($c);
			curl_multi_remove_handle($mh, $c);
		}
		curl_multi_close($mh);
		return $result;
	}


	function createLeads($emails,$url, $category){
		$category_array = explode(',', $category);
			
				
		foreach ($emails as  $email) {
			$existing_email=$this->add('xepan\base\Model_Contact_Email');
			$existing_email->addCondition('value',$email);
			$existing_email->tryLoadAny();
			try{
				if(!$existing_email->loaded()){
					$lead=$this->add('xepan\marketing\Model_Lead');
					$email_parts = explode("@", $email);
					$lead['first_name'] = $email_parts[0];
					unset($email_parts[0]);
					$lead['last_name'] = "@" . implode("", $email_parts);
					$lead['website'] = $url;
					$lead['source'] = 'Data Grabber';
					$lead->save();

					foreach ($category_array as $cat) {
						$associate_m = $this->add('xepan\marketing\Model_Lead_Category_Association');
						$associate_m['lead_id'] = $lead->id;
		     			$associate_m['marketing_category_id']= $cat;
			 			$associate_m->save();	
					}

					$email_info=$this->add('xepan\base\Model_Contact_Email');
					$email_info['contact_id']=$lead->id;
					$email_info['head']="Official";
					$email_info['value'] = $email;
					$email_info->save();
				}else{
					foreach ($category_array as $cat) {	
						$associate_m = $this->add('xepan\marketing\Model_Lead_Category_Association');
						$associate_m->addCondition('lead_id',$existing_email['contact_id']);
		     			$associate_m->addCondition('marketing_category_id',$cat);
		     			$associate_m->tryLoadAny();
		     			
		     			if(!$associate_m->loaded())
		 					$associate_m->save();	
					}
				}
			}catch(\Exception $e){
				// echo $email;
			}			
		}
	}
}