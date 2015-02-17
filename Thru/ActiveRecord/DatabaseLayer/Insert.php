<?php
namespace Thru\ActiveRecord\DatabaseLayer;

class Insert extends VirtualQuery
{
    protected $data;

    public function __construct($table, $alias = null)
    {
        $table_object = new Table($table);
        $alias = $alias!==null?$alias:$table_object->getAlias();
        $this->tables[$alias] = $table_object;
    }

    public function setData($data){
        $this->data = $data;
        return $this;
    }

    public function getData(){
        return $this->data;
    }
}