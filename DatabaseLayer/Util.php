<?php
namespace FourOneOne\ActiveRecord\DatabaseLayer;

use FourOneOne\ActiveRecord\Exception;

class Util extends VirtualQuery
{

    public function __construct()
    {
    }

    public function getIndexes($table){
        return $this->getInterpreter()->getIndexes($table);
    }


}