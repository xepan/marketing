<?php

namespace xepan\marketing;

class Page_configuration extends \xepan\base\Page
{
	
	function init()
	{
		parent::init();

		$epan_config=$this->app->epan->config;
		$freez_gap=$epan_config->getConfig('newsletter_freez_days','marketing');

		if(!$freez_gap){
			$temp = 0;
		}
		else{
			$temp = $freez_gap;
		}

		$form = $this->add('Form',null,'newsletter_configuration');
		$form->addField('line','day_gap')->set($temp);
		$form->addSubmit('Update');

		if($form->issubmitted()){
						
			$epan_config->setConfig('newsletter_freez_days',$form['day_gap'],'marketing');

			$form->js(null,$form->js()->reload())->univ()->successMessage('Information Updated')->execute();
		}
	}

	function defaultTemplate(){
		return['page\configuration'];
	}
}