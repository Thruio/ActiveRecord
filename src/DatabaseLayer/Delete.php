<?php
namespace Thru\ActiveRecord\DatabaseLayer;

class Delete extends VirtualQuery
{
    protected $data;

    public function __construct($table, $alias = null)
    {
        $this->tables[$alias] = new Table($table);
    }
}
