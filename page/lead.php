<?php

namespace xepan\marketing;
	
class page_lead extends \Page{
	public $title = "Lead";
	function init(){
		parent::init();

		// $category_id = $this->api->stickyGET('category_id');

		$lead = $this->add('xepan\marketing\Model_Lead');

		// if(is_numeric($category_id)){
		// 	$lead->addCondition('category_id',$category_id);
		// }

		$crud = $this->add('xepan\hr\CRUD',['action_page'=>'xepan_marketing_leaddetails'],null,['grid/lead-grid']);
		$crud->setModel($lead);
		$crud->grid->addQuickSearch(['name']);

		// $g = $crud->grid;
		// //Filter Form category wise
		// $form = $g->add('Form',null,'filter',['form/empty'])->addClass('pull-right');
		//Add Fields
		// $category_field = $form->addField('DropDown','category');
		// $category_field->setModel('xepan/marketing/Model_MarketingCampaign');
		// $category_field->setEmptyText('Select Category');
		// $category_field->set($category_id);
		// //adding custom submit button
		// $form_btn = $category_field->add('Button',null,'after_field')->set(['go','icon'=>'x fa fa-search']);
		
		
		//Form Search Btn
		// $form_btn->js('click',$form->js()->submit());

		// if(is_numeric($category_id)){
		// 	$form_reset_btn = $category_field->add('Button',null,'before_field')->set(['','icon'=>'x fa fa-times ']);
		// 	//Form Reset Btn
		// 	$self = $this;
		// 	$form_reset_btn->on('click',function($f)use($self){
		// 		$self->api->stickyForget('category_id');
		// 		return $self->js()->univ()->location($self->api->url(null));
		// 	});
		// }

		//form submission handle
		// $form->onSubmit(function($form){
		// 		if(!$form['category'])
		// 		return $form->error('category','please select');

		// 	return $form->js()->univ()->location($form->api->url(null,['category_id'=>$form['category']]));
		// });

		// $this->add('CRUD')->setModel('xepan/marketing/MarketingCampaign');

		
	}
}