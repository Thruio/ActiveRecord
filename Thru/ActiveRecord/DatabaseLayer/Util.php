<?php
namespace Thru\ActiveRecord\DatabaseLayer;

use Thru\ActiveRecord\Exception;

class Util extends VirtualQuery
{

    public function __construct()
    {
    }

    public function getIndexes($table){
        return $this->getInterpreter()->getIndexes($table);
    }


}