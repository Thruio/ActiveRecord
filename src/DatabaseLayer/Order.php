<?php
namespace Thru\ActiveRecord\DatabaseLayer;

class Order
{
    private $column;
    private $direction;

    public function __construct($column, $direction)
    {
        $this->setColumn($column);
        $this->setDirection($direction);
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
    public function getDirection()
    {
        return $this->direction;
    }
    public function setDirection($direction)
    {
        $this->direction = $direction;
        return $this;
    }
}
