<?php
namespace xepan\marketing;
class page_newslettertemplate extends \xepan\base\Page{
	public $title="Newsletter Template";
	public $breadcrumb=['Home'=>'index','Newsletter'=>'xepan_marketing_newsletter','Template'=>'#'];

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

		$newsletter = $this->add('xepan\marketing\Model_Newsletter');
		$newsletter->addCondition('is_template',true);
		$crud=$this->add('xepan\hr\CRUD',['entity_name'=>'Newsletter Template'],null,['grid/newslettertemplate-grid']);
		$crud->setModel($newsletter,['content_name','title','message_blog','marketing_category_id']);
		
		$vp = $this->add('VirtualPage');
		$vp->set(function($p){
			$newsletter_model = $this->add('xepan\marketing\Model_Newsletter')->load($_GET['newsletter_id']);
			
			$nv = $p->add('View');
			$nv->template->trySetHTML('Content',$newsletter_model['message_blog']);
		});	


		$this->on('click','.newsletter-preview',function($js,$data)use($vp){
				return $js->univ()->dialogURL("NEWSLETTER PREVIEW",$this->api->url($vp->getURL(),['newsletter_id'=>$data['id']]));
		});
		
		if($crud->isEditing()){
			$field = $crud->form->getElement('message_blog');
			$field->setFieldHint('Hint : type {$unsubscribe} for unsubscription link');
			$field->options=['templates'=> $templates_vp->getURL(),'relative_urls'=> true];
		}
	}
}