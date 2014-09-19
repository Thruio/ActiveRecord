<?php
namespace FourOneOne\ActiveRecord\DatabaseLayer;

class Condition
{

    private $column;
    private $value;
    private $operation;

    public function __construct($column, $value, $operation = '=')
    {
        $this->column = $column;
        $this->value = $value;
        $this->operation = $operation;
    }

    public function getColumn(){
        return $this->column;
    }
    public function setColumn($column){
        $this->column = $column;
        return $this;
    }
    public function getValue(){
        return $this->value;
    }
    public function setValue($value){
        $this->value = $value;
        return $this;
    }
    public function getOperation(){
        return $this->operation;
    }
    public function setOperation($operation){
        $this->operation = $operation;
        return $this;
    }

}