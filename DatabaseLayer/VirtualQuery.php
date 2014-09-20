<?php
namespace Thru\ActiveRecord\DatabaseLayer;

use Thru\ActiveRecord\Exception;

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

    public function getConditions(){
        return $this->conditions;
    }

    public function getLimit(){
        return $this->limit;
    }

    public function getOffset(){
        return $this->offset;
    }

    public function setModel($model){
        // TODO: Check Model is valid
        $this->model = $model;
        return $this;
    }

    public function getModel(){
        return $this->model;
    }

    public function addOrder(Order $order){
        $this->orders[] = $order;
        return $this;
    }

    public function getOrders(){
        return $this->orders;
    }

    /**
     * @return \Thru\ActiveRecord\DatabaseLayer\Sql\Base
     */
    protected function getInterpreter(){
        $sql_interpreter_name = "\\Thru\\ActiveRecord\\DatabaseLayer\\Sql\\" . \Thru\ActiveRecord\DatabaseLayer::get_instance()->get_option('db_type');
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
        if($model){
            $this->setModel($model);
        }
        $result = $this->getInterpreter()->process($this);
        global $active_record_log;
        $active_record_log['QueryCount'] = isset($active_record_log['QueryCount']) ? $active_record_log['QueryCount'] + 1 : 1;
        $active_record_log['QueryLog'][] = $this;
        return $result;
    }

    public function getOperation(){
        $class_bits = explode("\\", get_called_class());
        return end($class_bits);
    }


}