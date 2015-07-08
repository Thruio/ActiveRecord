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

    public function getColumn()
    {
        return $this->column;
    }

    public function getOperation()
    {
        return $this->operation;
    }

    public function getValue()
    {
        return $this->value;
    }
}
