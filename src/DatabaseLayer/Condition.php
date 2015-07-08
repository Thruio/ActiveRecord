<?php
namespace Thru\ActiveRecord\DatabaseLayer;

class Condition
{

    private $column;
    private $value;
    private $operation;

    public function __construct($column, $value, $operation = '=')
    {
        $this->setColumn($column);
        $this->setValue($value);
        $this->setOperation($operation);
    }

    public function getColumn()
    {
        return $this->column;
    }
    public function setColumn($column)
    {
        $this->column = $column;
        return $this;
    }
    public function getValue()
    {
        return $this->value;
    }
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }
    public function getOperation()
    {
        return $this->operation;
    }
    public function setOperation($operation)
    {
        $this->operation = $operation;
        return $this;
    }
}
