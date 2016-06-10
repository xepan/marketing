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
			$tmps = scandir($path);
			unset($tmps[0]);
			unset($tmps[1]);
			foreach ($tmps as $template) {
				if(file_exists($path.'/'.$template. "/index.html")){
					$content = file_get_contents($path.'/'.$template.'/index.html');
					// replace rel 2 abs URL/Path
					$domain = $this->app->pm->base_url.$this->app->pm->base_path.'vendor/xepan/marketing/templates/newsletter-templates/'.$template.'/';
					
					$content = preg_replace("/(href|src)\s*\=\s*[\"\']([^(http)])(\/)?/", "$1=\"$domain$2", $content);
					$content = preg_replace('/url\(\s*[\'"]?\/?(.+?)[\'"]?\s*\)/i', 'url('.$domain.'$1)', $content);
					
					$templates[] = [ 'title'=> $template, 'description'=> '', 'content'=> $content];
				}
			}

			echo json_encode($templates);
			exit;
		});


		$action = $this->api->stickyGET('action')?:'view';
		$nwl_id = $this->app->stickyGET('content_id');

		$newsletter = $this->add('xepan\marketing\Model_Newsletter')->tryLoadBy('id',$this->api->stickyGET('document_id'));
		$newsletter->addCondition('created_by_id',$this->app->employee->id);
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


		$nv = $this->add('xepan\hr\View_Document',['action'=>$action,'id_field_on_reload'=>'content_id'],null,['view/newsletterdesign']);
		$nv->setModel($newsletter,['title','message_blog','marketing_category','created_by','created_at'],['title','message_blog','marketing_category_id']);
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