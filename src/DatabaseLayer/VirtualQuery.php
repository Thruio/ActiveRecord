<?php
namespace Thru\ActiveRecord\DatabaseLayer;

use Thru\ActiveRecord\ActiveRecord;

class VirtualQuery
{
    protected $tables;      // SELECT/UPDATE/DELETE
    protected $conditions;  // WHERE
    protected $limit;       // LIMIT
    protected $offset;      // OFFSET
    protected $orders;      // ORDER BY *
    protected $model = 'StdClass';

    public function getTables()
    {
        return $this->tables;
    }

    public function setTables($tables)
    {
        $this->tables = $tables;
        return $this;
    }

    public function getConditions()
    {
        return $this->conditions;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function getOffset()
    {
        return $this->offset;
    }

    public function setModel($model)
    {
        if (is_object($model)) {
            $model = get_class($model);
        }
        $this->model = $model;
        return $this;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function addOrder(Order $order)
    {
        $this->orders[] = $order;
        return $this;
    }

    public function getOrders()
    {
        return $this->orders;
    }

    /**
     * @return \Thru\ActiveRecord\DatabaseLayer\Sql\Base
     */
    public function getInterpreter()
    {
        $sql_interpreter_name = "\\Thru\\ActiveRecord\\DatabaseLayer\\Sql\\" . \Thru\ActiveRecord\DatabaseLayer::getInstance()->getOption('db_type');
        $sql_interpreter = $sql_interpreter_name::factory();
        return $sql_interpreter;
    }

    /**
     * @param $column
     * @param $value
     * @param string $operation
     * @return $this
     */
    public function condition($column, $value, $operation = '=')
    {
        $this->conditions[] = new Condition($column, $value, $operation);
        return $this;
    }

    public function execute($model = null)
    {
        if ($model) {
            if ($model instanceof ActiveRecord) {
                $model = $model->getClass();
            }
            $this->setModel($model);
        }
        $interpreter = $this->getInterpreter();
        $result = $interpreter->process($this);
        return $result;
    }

    public function getOperation()
    {
        $class_bits = explode("\\", get_called_class());
        return end($class_bits);
    }
}
