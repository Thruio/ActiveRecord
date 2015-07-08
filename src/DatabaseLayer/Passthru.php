<?php
namespace Thru\ActiveRecord\DatabaseLayer;

class Passthru extends VirtualQuery
{
    private $sql_to_passthru;

    public function __construct($sql = null)
    {
        if ($sql) {
            $this->sql_to_passthru = $sql;
        }
    }

    public function query($sql)
    {
        $this->sql_to_passthru = $sql;
        return $this;
    }

    public function get_sql_to_passthru()
    {
        return $this->sql_to_passthru;
    }
}
