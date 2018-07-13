<?php
namespace xepan\marketing;
class page_newsletterdesign extends \xepan\base\Page{
	public $title="Newsletter Design";
	public $breadcrumb=['Home'=>'index','Newsletter'=>'xepan_marketing_newsletter','Design'=>'#'];

	function init(){
		parent::init();	

		$templates_vp = $this->add('VirtualPage');
		$templates_vp->set(function($p){
			$templates = [];
			$path=realpath(getcwd().'/vendor/xepan/marketing/templates/newsletter-templates');			
			foreach (new \DirectoryIterator($path) as $template){
				if($template->isDot() or !$template->isDir()) continue;
				$template_name=$template->getFilename();
				if(file_exists($path.'/'.$template_name. "/index.html")){
					$content = file_get_contents($path.'/'.$template_name.'/index.html');
					// replace rel 2 abs URL/Path
					$domain = $this->app->pm->base_url.$this->app->pm->base_path.'vendor/xepan/marketing/templates/newsletter-templates/'.$template_name.'/';
					
					$content = preg_replace("/(href|src)\s*\=\s*[\"\']([^(http)])(\/)?/", "$1=\"$domain$2", $content);
					$content = preg_replace('/url\(\s*[\'"]?\/?(.+?)[\'"]?\s*\)/i', 'url('.$domain.'$1)', $content);
					
					$templates[] = [ 'title'=> $template_name, 'description'=> '', 'content'=> $content];
				}
			}

			echo json_encode($templates);
			exit;
		});


		$action = $this->api->stickyGET('action')?:'view';
		$nwl_id = $this->app->stickyGET('content_id');

		$newsletter = $this->add('xepan\marketing\Model_Newsletter')->tryLoadBy('id',$this->api->stickyGET('document_id'));

		$newsletter->addHook('beforeSave',function($m){
			$htmlContent = $m['message_blog'];

			$origImageSrc = [];
			// read all image tags into an array
			preg_match_all('/<img[^>]+>/i',$htmlContent, $imgTags); 

			for ($i = 0; $i < count($imgTags[0]); $i++) {
			  // get the source string
			  preg_match('/src="([^"]+)/i',$imgTags[0][$i], $imgage);

			  // remove opening 'src=' tag, can`t get the regex right
			  $origImageSrc[] = str_ireplace( 'src="', '',  $imgage[0]);
			}

			// will output all your img src's within the html string
			$error = [];
			foreach ($origImageSrc as $image) {
				$temp = explode("://", $image);
				if($temp[0] == "http" or $temp[0] == "https") continue;
				
				if(!file_exists($image)){
					$error[] = $image;
				}
			}
			if(count($error)>0){
				$msg = 'NOT SAVED: The following files not found in place, sending will prodcuce error, please solve them - <br/>';
				$i=1;
				foreach ($error as $e1) {
					$msg .= $i.": ".$e1 ."<br/>";
					$i++;
				}
				$e = $this->exception($msg,'ValidityCheck')->setField('message_blog');
				throw $e;
			}			
		});

		if($action !='view'){
			$tmps = scandir(getcwd().'/../vendor/xepan/marketing/templates/newsletter-layout-chunks');
			unset($tmps[0]);
			unset($tmps[1]);

			foreach ($tmps as $template) {
				if(strpos($template, ".html")===false) continue;
				$x =$this->add('View',null,null,['newsletter-layout-chunks/'.str_replace(".html", '', $template)],'preview');//->set(str_replace(".html", '', ucwords($template)));
				$x->js(true)->draggable([
					'helper'=>$x->js(null,'return $(this).find(".template").clone().show()')->_enclose()
					]);	
			}
		}

		$newsletter->getElement('message_blog')->hint('{$first_name},{$last_name},{$name},{$organization},{$website},{$search_string},{$emails_str},{$contacts_str} ');
		$nv = $this->add('xepan\hr\View_Document',['action'=>$action,'id_field_on_reload'=>'document_id','page_reload'=>true],null,['view/newsletterdesign']);
		$nv->setModel($newsletter,['content_name','title','message_blog','marketing_category','created_by','created_at','is_template'],['content_name','title','message_blog','marketing_category_id','is_template']);
		$nv->add('xepan\base\Controller_Avatar',['options'=>['size'=>50,'border'=>['width'=>0]],'name_field'=>'created_by','default_value'=>'']);
		
		if($action ==='add' && $nwl_id){			
			$content = $this->add('xepan\marketing\Model_Content');
			$content->load($nwl_id);				
			$nv->form->getElement('message_blog')->set($content['message_blog']);
		}

		if($action !='view'){			
			$field = $nv->form->getElement('message_blog');
			$field->options=['templates'=> $templates_vp->getURL(),'relative_urls'=> true];
		}
	}
}