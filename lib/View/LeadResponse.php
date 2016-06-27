<?php

namespace xepan\marketing;

class View_LeadResponse extends \View{
  function init(){
    parent::init();
    
    $this->setModel('xepan\marketing\Dashboard');
  }
}