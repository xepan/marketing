<?php

namespace xepan\marketing;
	
class page_lead extends \xepan\base\Page{
	public $title = "Lead";
	public $content=null;
	public $model_class;
	public $crud;
	public $filter_form;

	function page_index(){

		$vp = $this->add('VirtualPage');
		$vp->set(function($p){
			try{
				$lead = $this->add('xepan\marketing\Model_Lead')->load($_POST['pk']);
				$lead->ref('xepan\marketing\Lead_Category_Association')->deleteAll();
				foreach ($_POST['value']?:[] as $catagory_id) {
					$this->add('xepan\marketing\Model_Lead_Category_Association')
						->set('lead_id',$_POST['pk'])
						->set('marketing_category_id',$catagory_id)
						->saveAndUnload();
				}
			}catch(\Exception $e){
				http_response_code(400);
				echo $e->getMessage();
			}
			exit;
			
		});
		
		if($this->model_class){
			$lead = $this->add($this->model_class);
			
		}else{
			$lead = $this->add('xepan\marketing\Model_Lead');
		}

		// $lead->getElement('days_ago')->destroy();
		// $lead->getElement('last_communication')->destroy();
		// $lead->getElement('last_landing_response_date_from_lead')->destroy();
		// $lead->getElement('last_communication_date_from_lead')->destroy();
		// $lead->getElement('last_communication_date_from_company')->destroy();


		if($src = $this->app->stickyGET('source'))
			$lead->addCondition('source',$src);
		
		if($strt = $this->app->stickyGET('start_date'))
			$lead->addCondition('created_at','>=',$strt);
		
		if($end = $this->app->stickyGET('end_date'))
			$lead->addCondition('created_at','<=',$this->app->nextDate($end));

		if($category_id = $this->app->stickyGet('category_id')){
			$lead_assoc = $lead->join('lead_category_association.lead_id','id');
			$lead_assoc->addField('lead_category_id','marketing_category_id');

			$lead->addCondition('lead_category_id',$category_id);
			$lead->_dsql()->group('lead_id');
		}

		if($status = $this->app->stickyGET('status'))
			$lead->addCondition('status',$status);

		$lead->addExpression('existing_associated_catagories')->set(function($m,$q){
			$x = $m->add('xepan\marketing\Model_Lead_Category_Association',['table_alias'=>'lead_cat_assos']);
			return $x->addCondition('lead_id',$q->getField('id'))->_dsql()->del('fields')->field($q->expr('group_concat([0])',[$x->getElement('marketing_category_id')]));
		});

		$lead->addExpression('organization_name_with_name')
					->set($lead->dsql()
						->expr('CONCAT(IFNULL([0],"")," ::[ ",IFNULL([1],"")," ]")',
							[$lead->getElement('first_name'),
								$lead->getElement('organization')]))
					->sortable(true);

		$lead->add('xepan\marketing\Controller_SideBarStatusFilter');
		// $lead->setOrder('total_visitor','desc');

		$this->crud = $crud = $this->add('xepan\hr\CRUD',['action_page'=>'xepan_marketing_leaddetails'],null,['grid/lead-grid']);
		$crud->setModel($lead,['emails_str','contacts_str','name','organization_name_with_name','source','city','type','score','total_visitor','created_by_id','created_by','assign_to_id','assign_to','effective_name','code','organization','existing_associated_catagories','created_at','priority'])->setOrder('created_at','desc');
		$crud->grid->addPaginator(50);
		$crud->add('xepan\base\Controller_MultiDelete');

		if(!$crud->isEditing()){
			$catagories = $this->add('xepan\marketing\Model_MarketingCategory');
			$value =[];
			foreach ($catagories as $cc) {
				$value[]=['value'=>$cc->id,'text'=>$cc['name']];
			}

			$quick_edit_permission =false;

			if($this->app->auth->model->isSuperUser()) $quick_edit_permission = true;

			$crud->grid->js(true)->_load('bootstrap-editable.min')->_css('libs/bootstrap-editable')->_selector('.catagory-associated')->editable(
				[
				'url'=>$vp->getURL(),
				'limit'=> 3,
				'source'=> $value,
				'disabled'=> !$quick_edit_permission
				]);
		}

		$grid=$crud->grid;
		$grid->addClass('grab-lead-grid');
		$grid->js('reload')->reload();

		$crud->add('xepan\base\Controller_Avatar');
		
		$this->filter_form = $frm = $grid->addQuickSearch(['name','organization','emails_str','contacts_str','score']);
	
		$category_filter_field = $frm->addField('Dropdown','marketing_category_id')->setEmptyText('Categories');
		$category_filter_field->setModel('xepan\marketing\MarketingCategory');

		if($_GET['category_id'])
			$category_filter_field->js(true)->val($_GET['category_id']);

		$source_type = $frm->addField('Dropdown','source_type')->setEmptyText('Please Select Source');
		$source_model = $this->add('xepan\base\Model_ConfigJsonModel',
		        [
		            'fields'=>[
						'lead_source'=>'text',
						],
					'config_key'=>'MARKETING_LEAD_SOURCE',
					'application'=>'marketing'
		        ]);
		$source_model->tryLoadAny();
		$source_array = explode(",",$source_model['lead_source']);
		$source_type->setValueList(array_combine($source_array,$source_array));

		// employee filter created by
		$emp_field = $frm->addField('Dropdown','filter_employee_id');
		$emp_field->setModel('xepan\hr\Model_Employee')->addCondition('status','Active');
		$emp_field->setEmptyText('Created By Employee');

		// employee filter assign to
		$emp_assign_field = $frm->addField('Dropdown','filter_employee_assign_to_id');
		$emp_assign_field->setModel('xepan\hr\Model_Employee')->addCondition('status','Active');
		$emp_assign_field->setEmptyText('Assign to Employee');

		$frm->addHook('applyFilter',function($f,$m){
			if($f['marketing_category_id']){
				$cat_asso = $this->add('xepan\marketing\Model_Lead_Category_Association');
				$cat_asso->addCondition('marketing_category_id',$f['marketing_category_id']);
				$m->addCondition('id','in',$cat_asso->fieldQuery('lead_id'));
			}
			if($f['source_type']){
				$m->addCondition('source',$f['source_type']);
			}

			if($f['filter_employee_id']){
				$m->addCondition('created_by_id',$f['filter_employee_id']);
			}

			if($f['filter_employee_assign_to_id']){
				$m->addCondition('assign_to_id',$f['filter_employee_assign_to_id']);
			}

		});
		
		// $category_filter_field->js('change',$grid->js()->reload(null,null,[$this->app->url('.'),'category_id'=>$category_filter_field->js()->val()]));
		$category_filter_field->js('change',$frm->js()->submit());
		$source_type->js('change',$frm->js()->submit());
		$emp_field->js('change',$frm->js()->submit());
		$emp_assign_field->js('change',$frm->js()->submit());


		// $grid->addColumn('category');
		// $grid->addMethod('format_marketingcategory',function($grid,$field){				
		// 		$data = $grid->add('xepan\marketing\Model_Lead_Category_Association')->addCondition('lead_id',$grid->model->id);
		// 		$l = $grid->add('Lister',null,'category',['grid/lead-grid','category_lister']);
		// 		$l->setModel($data);
				
		// 		$grid->current_row_html[$field] = $l->getHtml();
		// });

		// $grid->addFormatter('category','marketingcategory');
		$grid->js(true)->_load('jquery.sparkline.min')->_selector('.sparkline')->sparkline('html', ['enableTagOptions' => true]);
		if(!$crud->isEditing()){
			$grid->js('click')->_selector('.do-view-lead')->univ()->frameURL('Lead Details',[$this->api->url('xepan_marketing_leaddetails'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
			$grid->js('click')->_selector('.do-view-lead-visitor')->univ()->frameURL('Total Visits',[$this->api->url('xepan_marketing_leadvisitor'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
			$grid->js('click')->_selector('.do-view-lead-score')->univ()->frameURL('Total Score',[$this->api->url('xepan_marketing_leadscore'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
		}

		$btn = $grid->addButton('Grab')->addClass('btn btn-primary');
		$btn->js('click',$this->js()->univ()->frameURL('Data Grabber',$this->app->url('./grab')));

		/**			
		CSV Importer
		*/
		$import_btn=$grid->addButton('Import CSV')->addClass('btn btn-primary');
		$import_btn->setIcon('ui-icon-arrowthick-1-n');

		$import_btn->js('click')
			->univ()
			->frameURL(
					'Import CSV',
					$this->app->url('./import')
					);

	}

	function page_import(){
		
		$form = $this->add('Form');
		$form->addSubmit('Download Sample File');
		
		if($_GET['download_sample_csv_file']){
			$output = ['first_name','last_name','address','city','state','country','pin_code','organization','post','website','source','remark','personal_email_1','personal_email_2','official_email_1','official_email_2','personal_contact_1','personal_contact_2','official_contact_1','official_contact_2','category'];

			$output = implode(",", $output);
	    	header("Content-type: text/csv");
	        header("Content-disposition: attachment; filename=\"sample_xepan_lead_import.csv\"");
	        header("Content-Length: " . strlen($output));
	        header("Content-Transfer-Encoding: binary");
	        print $output;
	        exit;
		}

		if($form->isSubmitted()){
			$form->js()->univ()->newWindow($form->app->url('xepan_marketing_lead_import',['download_sample_csv_file'=>true]))->execute();
		}

		$this->add('View')->setElement('iframe')->setAttr('src',$this->api->url('./execute',array('cut_page'=>1)))->setAttr('width','100%');
	}
	
	function downloadsamplefile(){

	}

	function page_import_execute(){

		ini_set('max_execution_time', 0);

		$form= $this->add('Form');
		$form->template->loadTemplateFromString("<form method='POST' action='".$this->api->url(null,array('cut_page'=>1))."' enctype='multipart/form-data'>
			<input type='file' name='csv_lead_file'/>
			<input type='submit' value='Upload'/>
			</form>"
			);

		if($_FILES['csv_lead_file']){
			if ( $_FILES["csv_lead_file"]["error"] > 0 ) {
				$this->add( 'View_Error' )->set( "Error: " . $_FILES["csv_lead_file"]["error"] );
			}else{
				$mimes = ['text/comma-separated-values', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.ms-excel', 'application/vnd.msexcel', 'text/anytext'];
				if(!in_array($_FILES['csv_lead_file']['type'],$mimes)){
					$this->add('View_Error')->set('Only CSV Files allowed');
					return;
				}

				$importer = new \xepan\base\CSVImporter($_FILES['csv_lead_file']['tmp_name'],true,',');
				$data = $importer->get();

				$lead = $this->add('xepan\marketing\Model_Lead');
				$lead->addLeadFromCSV($data);
				$this->add('View_Info')->set('Total Records : '.count($data));
			}
		}
	}

	function page_grab(){
		$extra_info = $this->app->recall('epan_extra_info_array',false);

		if($extra_info['Data Grabber'] != "Yes"){
			$this->add('View')->addClass('alert alert-danger')->set('You are not permitted to use this services');
			return;
		}

		set_time_limit(0);
		// echo "TYpe of code : Google serach result page, website, portal, yahoo search result page, bing search result page <br/>";
		// echo "Selector for urls <br/>";
		// echo "Code Block :: text area <br/>";
		$array	=
				[
					''=>'Please Select',
					'h3 a'=>'Google Search Result Page',
					// 'Website Page'=>'Website Page',
					// 'Yahoo Search Result Page'=>'Yahoo Search Result Page',
					// 'Portal'=>'Portal',
					// 'Other'=>'Other'
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
			$category_array = explode(",", $category);			

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

			$this->api->db->beginTransaction();
			try {

				// get all emails and find existing leads first here
				// get all existing contact emails with their lead id
				$all_emails = [];
				$this->insert_sql =[];

				foreach ($unique_emails as $host => $emails) {
					$all_emails = array_merge($all_emails,$emails);
				}

				// echo "So all emails to be check are as follows <br/>";
				// var_dump($all_emails);

				if(count($all_emails)){
					$existing_email=$this->add('xepan\base\Model_Contact_Email');
					$existing_email->addCondition('value',$all_emails);
					$existing_lead_data = $existing_email->getRows();

					$already_in_database_emails = [];
					foreach ($existing_lead_data as $dt) {
						$contact_id = $dt['contact_id'];
						$already_in_database_emails [] = $dt['value'];
						foreach ($category_array as $cat_id) {
							$this->insert_sql [] = "INSERT IGNORE INTO lead_category_association (id, lead_id, marketing_category_id, created_at) VALUES (0,$contact_id,$cat_id,'".$this->app->now."'); ";
						}
					}
				}


				// echo "already in database emails <br/>";
				// var_dump($already_in_database_emails);

				foreach ($unique_emails as $host => $emails) {
					foreach ($emails as $em) {
						if(!in_array($em, $already_in_database_emails)) {
							// echo "creating lead for $em <br/>";
							$this->createLead($em,$host, $category_array);
						}
					}

				}

				// echo "<br/>".implode("<br/>", $this->insert_sql) ."<br/>";
				$this->app->db->dsql()->expr(implode("", $this->insert_sql))->execute();
				$this->api->db->commit();
	        }catch(Exception $e){
	            $this->api->db->rollback();
	            throw $e;
	        }

			$js=[
					$f->js()->closest('.dialog')->dialog('close'),
					$this->js()->_selector('.grab-lead-grid')->trigger('reload')
				];

			$f->js(null,$js)->univ()->successMessage('Leads Grabbed')->execute();
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


	function createLead($email,$url, $category_array){

		$company_info = $this->app->epan['name'];
		$owner_code = substr($company_info, 0,3);
		$code = $owner_code.'LEA';

		$email_parts = explode("@", $email);
		$first_name = $email_parts[0];
		unset($email_parts[0]);
		$last_name = "@" . implode("", $email_parts);
		$website = $url;
		$source = 'Data Grabber';

		$search_string = $first_name." ".$last_name." ".$website." ".$source." ".$email;
		$this->insert_sql [] = "INSERT INTO contact (id, first_name, last_name, type, website, source, created_at, updated_at,created_by_id,score,freelancer_type,search_string, status) VALUES (0,'$first_name','$last_name','Contact','$website','$source','".$this->app->now."','".$this->app->now."','".$this->app->employee->id."',0,'Not Applicable','$search_string','Active'); SET @last_lead_id = LAST_INSERT_ID();";
		$this->insert_sql [] = "UPDATE contact set code = concat('$code',@last_lead_id) WHERE id = @last_lead_id;";
		$this->insert_sql [] = "INSERT INTO contact_info (id, contact_id, head, value, is_active, is_valid, type) VALUES (0,@last_lead_id,'Official','$email',1,1,'Email');";
		foreach ($category_array as $cat_id) {
			$this->insert_sql[] = "INSERT IGNORE INTO lead_category_association (id, lead_id, marketing_category_id, created_at) VALUES (0,@last_lead_id,$cat_id,'".$this->app->now."'); ";
		}

	}
}