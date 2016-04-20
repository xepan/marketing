<?php
namespace xepan\marketing;
class page_newsletterdesign extends \xepan\base\Page{
	public $title="Newsletter Design";
	public $breadcrumb=['Home'=>'index','Newsletter'=>'xepan_marketing_newslettertemplate','Design'=>'#'];

	function init(){
		parent::init();	

		$action = $this->api->stickyGET('action')?:'view';
		$newsletter = $this->add('xepan\marketing\Model_Newsletter')->tryLoadBy('id',$this->api->stickyGET('document_id'));

		if($action !='view'){
			$tmps = scandir(getcwd().'/../vendor/xepan/marketing/templates/newsletter-templates');
			unset($tmps[0]);
			unset($tmps[1]);

			foreach ($tmps as $template) {
				if(strpos($template, ".html")===false) continue;
				$x =$this->add('View',null,null,['newsletter-templates/'.str_replace(".html", '', $template)],'preview');//->set(str_replace(".html", '', ucwords($template)));
				// $html = $this->add('View',null,null,['newsletter-templates/'.str_replace(".html", '', $template),'template']);
				// $html->setStyle('display','none');
				// $x->setAttr('data-html','"'.htmlentities($html->getHTML()).'"');
				$x->js(true)->draggable([
					'helper'=>$x->js(null,'return $(this).find(".template").clone().show()')->_enclose()
					]);	
			}

		}


		$nv = $this->add('xepan\hr\View_Document',['action'=>$action,'id_field_on_reload'=>'content_id'],null,['view/newsletterdesign']);
		$nv->setModel($newsletter,['title','message_blog','marketing_category','created_by','created_at'],['title','message_blog','marketing_category_id']);

	}
}