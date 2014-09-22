<?php
namespace Thru\ActiveRecord\DatabaseLayer;

use Thru\ActiveRecord\ActiveRecord;

class TableBuilder extends VirtualQuery{
  public function build(ActiveRecord $context){
    $this->getInterpreter()->buildTable($context);
  }
}