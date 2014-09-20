<?php
namespace Thru\ActiveRecord\DatabaseLayer;

use Thru\ActiveRecord\Exception;

class Delete extends VirtualQuery
{
    protected $data;

    public function __construct($table, $alias = null)
    {
        $this->tables[$alias] = new Table($table);
    }

}