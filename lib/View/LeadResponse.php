<?php

namespace xepan\marketing;

class View_LeadResponse extends \View{
  function init(){
    parent::init();
  }

  function defaultTemplate(){
    return ['view/leadresponse'];
  }
}