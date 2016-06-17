<?php


namespace xepan\marketing;

class Controller_SideBarStatusFilter extends \AbstractController{
	public $add_all=true;

	function init(){
		parent::init();

		if(!$this->owner instanceof \SQL_Model)
			throw $this->exception('Please add SideBarStatusFilter Controller on main model of page only')
						->addMoreInfo('current_owner',$this->owner);


		$count_m = $this->owner->owner->add(get_class($this->owner));
		$counts = $count_m->_dsql()->del('fields')->field('status')->field('count(*) counts')->group('Status')->get();
		$counts_redefined =[];
		$total=0;
		foreach ($counts as $cnt) {
			$counts_redefined[$cnt['status']] = $cnt['counts'];
			$total += $cnt['counts'];
		}
		$icon_array = $this->app->status_icon;
		$model_class=get_class($this->owner);
		if($this->add_all){
			$this->app->side_menu->addItem(['All','icon'=>$icon_array[$model_class]['All'],'badge'=>[$total,'swatch'=>' label label-primary label-circle pull-right']],$this->api->url(null,['status'=>null]),['status'])->setAttr(['title'=>'All']);
		}

		foreach ($this->owner->status as $s) {
			// $this->app->side_menu->addItem([$s,'icon'=>$icon_array[$model_class][$s],'badge'=>[$counts_redefined[$s],'swatch'=>' label label-primary label-circle pull-right']],$this->api->url(null,['status'=>$s]),['status'])->setAttr(['title'=>$s]);
			$this->app->side_menu->addItem([$s,'icon'=>$icon_array[$model_class][$s],'badge'=>[$counts_redefined[$s],'swatch'=>' label label-primary label-circle pull-right']],$this->api->url(null,['status'=>$s]),['status'])->setAttr(['title'=>$s]);
		}

		if($status=$this->api->stickyGET('status')){
			$this->owner->addCondition('status','in',explode(",",$status));
			$this->owner->owner->title .= ' ['.$status .' :'. $counts_redefined[$status] .']';
		}
	}
}