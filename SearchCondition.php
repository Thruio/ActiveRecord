<?php
namespace Thru\ActiveRecord;

class SearchCondition
{
    private $column;
    private $value;
    private $operation;

    public function __construct($column, $value, $operation)
    {
        $this->column = $column;
        $this->value = $value;
        $this->operation = $operation;
    }

    public function get_column()
    {
        return $this->column;
    }

    public function get_operation()
    {
        return $this->operation;
    }

    public function get_value()
    {
        return $this->value;
    }
}