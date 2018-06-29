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
		
		$lead->addExpression('lead_substr')
					->set($lead->dsql()
						->expr('CONCAT(IFNULL([0],"")," :: ",IFNULL([1],""), " :: ",IFNULL([2],""))',
							[$lead->getElement('unique_name'),
							$lead->getElement('emails_str'),
							$lead->getElement('contacts_str')]))
					->sortable(true);

		$col = $this->add('Columns');
		$col1 = $col->addColumn(10);
		$col2 = $col->addColumn(2);

		$form = $col1->add('Form');
		$form->add('xepan\base\Controller_FLC')
			->makePanelsCoppalsible(true)
			->layout([
				'category'=>'Filter Lead~c1~3',
				'lead'=>'c2~5',
				'FormButtons~&nbsp;'=>'c4~3'
			]);

		$view = $this->add('View');
		
		// $asso_m = $this->add('xepan\marketing\Model_Lead_Category_Association');
		// $asso_j = $lead->join('lead_category_association','id');
		// $asso_j->addField('marketing_category_id');
		
		// $category_id = $this->app->stickyGET('category_id');
		// if($category_id){
		// 	$lead->addCondition('marketing_category_id',$category_id);
		// }
		// $form->setLayout(['form/telemarketing-listview-form']);

		$cat_field = $form->addField('DropDown','category')->setEmptyText('Please Select Category');
		$cat_field->setModel($cat);
		$lead_field = $form->addField('xepan\base\Basic','lead');
		$lead_field->setModel($lead);
		$form->addSubmit('Get Details')->addClass('btn btn-success btn-block');
		
		$cat_field->js('change',$lead_field->js()->reload(null,null,[$this->app->url(null,['cut_object'=>$lead_field->name]),'category_id'=>$cat_field->js()->val()]));
		// $cat_field->js('change',$form->js()->atk4_form('reloadField','lead',[$this->app->url(),'category_id'=>$cat_field->js()->val()]));

		$list_view = $view->add('xepan\marketing\View_TeleMarketingListView');
		if($form->isSubmitted()){
			$form->js(null,$view->js()->reload(['contact_id'=>$form['lead']]))->execute();	
		}

		$btn = $col2->add('Button')->set('Create Lead')->addClass('btn btn-primary');
		$btn->js('click',
					$this->js()->univ()
							->frameURL(
									'Creating New Lead',
									$this->app->url(
										'xepan_marketing_leaddetails',
										['action'=>'add']
									)
								)
						);

	}
}