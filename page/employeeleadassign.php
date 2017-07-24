<?php

namespace xepan\marketing;


/**
* 
*/
class page_employeeleadassign extends \xepan\base\Page{

	public $title = "Employee Multiple Lead Assign";
	function init(){
		parent::init();
		$lead = $this->add('xepan\marketing\Model_Lead');
		$lead->addCondition('status','Active');

		$form = $this->add('Form',null,null,['form\empty']);
		$form->add('View')->set('Select Multiple Lead`s');
		$lead_field = $form->addField('xepan\base\DropDown','leads')->addClass('xepan-push');
		$form->add('View')->set('Select Employee to Assigned leads');
		$form->addField('xepan\base\Basic','employee')->setModel('xepan\hr\Employee');
		$form->addSubmit('Assign Lead')->addClass('btn btn-success');
		$lead_field->validate_values=false;
		if($_GET[$this->name.'_lead']){
			$results = [];
			$leads = $this->add('xepan\marketing\Model_Lead');
			$leads->addCondition('status','Active');
			$leads->setLimit(20);
			
			foreach ($leads as $lead) {
				$results[] = ['id'=>$lead->id,'text'=>$lead['name'].' <'.$lead['emails_str']." (".$lead['contacts_str'].")". '>'];
			}

			echo json_encode(
				[
					"results" => $results,
					"more"=>false	
				]
				);
			exit;
		}
		$lead_field->select_menu_options = 
			[	
				'width'=>'100%',
				'tags'=>true,
				'tokenSeparators'=>[',','\n\r'],
				'ajax'=>[
					'url' => $this->api->url(null,[$this->name.'_lead'=>true])->getURL(),
					'dataType'=>'json'
				]
			];

		$lead_field->setAttr('multiple','multiple');

		if($form->isSubmitted()){
			foreach (explode(",",$form['leads']) as $id) {
				if(is_numeric(trim($id))){
					$lead_m = $this->add('xepan\marketing\Model_Lead');
					$lead_m->tryLoad($id);
					if(!$lead_m->loaded())
						return $form->error('leads','Value '.$id.' is not acceptable...');

					$lead_m['assign_to_id'] = $form['employee'];
					$lead_m->save();
				}
			}

			$form->js(null,$form->js()->univ()->successMessage('Multiple Lead Assigned'))->reload()->execute();
		}
	}
}