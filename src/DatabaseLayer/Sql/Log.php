<?php
namespace Thru\ActiveRecord\DatabaseLayer\Sql;

class Log
{
    protected $query;
    protected $exec_time;
    protected $call_time;

    public function __construct($query, $exec_time){
        $this->query = $query;
        $this->exec_time = $exec_time;
        $this->call_time = microtime(true);
    }
}
