<?php
namespace Thru\ActiveRecord\DatabaseLayer;

class LockController extends VirtualQuery
{

    public function __construct($table, $alias = null)
    {
        $this->tables[$alias] = new Table($table);
    }

    public function lock()
    {

    }

    public function unlock()
    {

    }
}
