<?php
namespace Thru\ActiveRecord\DatabaseLayer;

use Thru\ActiveRecord\Exception;

class Insert extends VirtualQuery
{
    protected $data;

    public function __construct($table, $alias = null)
    {
        $this->tables[$alias] = new Table($table);
    }

    public function setData($data){
        $this->data = $data;
        return $this;
    }

    public function getData(){
        return $this->data;
    }
}