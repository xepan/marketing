<?php

namespace xepan\marketing;


/**
* 
*/
class page_telemarketinglistview extends \xepan\base\Page{

	public $title = "Telemarketing List View";
	function init(){
		parent::init();

		$contact_id = $this->app->stickyGET('contact_id');

		$cat = $this->add('xepan\marketing\Model_MarketingCategory');
		$lead = $this->add('xepan\marketing\Model_Lead');
		$lead->title_field ="lead_substr";
		$lead->addExpression('lead_substr')->set($lead->dsql()->expr('CONCAT([0]," :: ",[1], " :: ",[2])',[$lead->getElement('unique_name'),$lead->getElement('emails_str'),$lead->getElement('contacts_str')]))->sortable(true);

		$form = $this->add('Form',null,null,['form/empty']);
		$view = $this->add('View');

		$form->setLayout(['form/telemarketing-listview-form']);
		$cat_field = $form->addField('DropDown','category');
		$cat_field->setModel($cat);
		$lead_field = $form->addField('xepan\base\Basic','lead');
		$lead_field->setModel($lead);
		$form->addSubmit('Get Details')->addClass('btn btn-success btn-block');

		$list_view = $view->add('xepan\marketing\View_TeleMarketingListView');
		if($form->isSubmitted()){
			$form->js(null,$view->js()->reload(['contact_id'=>$form['lead']]))->execute();	
		}

	}
}