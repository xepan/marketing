<?php


namespace xepan\marketing;

class View_EasySetupWizard extends \View{
	function init(){
		parent::init();
		if($this->add('xepan\hr\Model_Department')->count()->getOne() <= 1000){
			$v = $this->add('xepan\base\View_Wizard_Step');
			$v->setTitle('Lorem ipsum dolor sit amet');
			$v->setMessage('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam ut porta massa, sit amet maximus odio.');
			$v->setHelpURL('#');
			$v->setAction('I M ATION',$v->js()->reload());

			$v1 = $this->add('xepan\base\View_Wizard_Step');
			$v1->setTitle('Lorem ipsum dolor sit amet');
			$v1->setMessage('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam ut porta massa, sit amet maximus odio.');
			$v1->setHelpURL('#');
			$v1->setAction('I M ATION',$v->js()->reload());


		}
	}
}