<?php
namespace Thru\ActiveRecord;

use Thru\ActiveRecord\DatabaseLayer\IndexException;
use Thru\ActiveRecord\DatabaseLayer\TableBuilder;

class Search
{
    private $model;
    private $conditions;
    private $order;
    private $limit;
    private $offset = 0;

    public function __construct($model)
    {
        $this->model = $model;
    }

    public function where($column, $value, $operation = null)
    {
        if (!$operation) {
            $operation = '=';
        }
        $this->conditions[] = new SearchCondition($column, $value, $operation);
        return $this;
    }

    public function condition(SearchCondition $condition)
    {
        $this->conditions[] = $condition;
        return $this;
    }

    public function limit($limit, $offset = 0)
    {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    public function order($column, $direction = 'DESC')
    {
        $this->order[] = array('column' => $column, 'direction' => $direction);
        return $this;
    }

    public function exec()
    {
        $primary_key_search = false;
        if (count($this->conditions) == 1) {
          /* @var $model ActiveRecord */
            $model = $this->model;

            if (end($this->conditions)->getColumn() == $model->getTablePrimaryKey() && end($this->conditions)->getOperation() == '=') {
                $primary_key_search = true;
                if (SearchIndex::getInstance()->exists($model->getTableName(), end($this->conditions)->get_value())) {
                    return array(
                    SearchIndex::getInstance()
                    ->get($model->getTableName(), end($this->conditions)->get_value())
                    );
                }
            }
            unset($model);
        }

        $database = DatabaseLayer::getInstance();

        $select = $database->select($this->model->getTableName(), $this->model->getTableAlias());
        $select->fields($this->model->getTableAlias());

        // Add WHERE Conditions
        foreach ((array)$this->conditions as $condition) {
            $select->condition($condition->getColumn(), $condition->getValue(), $condition->getOperation());
        }

        if ($this->order) {
            foreach ($this->order as $order) {
                $select->orderBy($order['column'], $order['direction']);
            }
        }

        // Build LIMIT SQL if relevent
        if ($this->limit) {
            $select->range($this->offset, $this->limit);
        }

        // Get objects
        $class = get_class($this->model);
        $select->setModel($class);
        $response = $select->execute();
        return $this->execProcessResponse($response, $primary_key_search);
    }

    private function execProcessResponse($response, $primary_key_search)
    {
        $results = array();

        foreach ($response as $result) {
          /* @var $result ActiveRecord */
            if ($result->getPrimaryKeyIndex()) {
                $primary_key_column = $result->getPrimaryKeyIndex()[0];
                $results[$result->$primary_key_column] = $result;
            } else {
                $results[] = $result;
            }
        }

        foreach ($results as $result) {
            $result->__fieldFix();
        }

      // Check for ActiveRecord_class and recast as needed
      /*foreach ($results as $key => $result) {
        $results[$key] = $result->__recast();
      }*/

      // Call __post_construct on each of the newly constructed objects.
        foreach ($results as &$result) {
          /* @var $result ActiveRecord */
            $result->postConstruct();
          /*if ($result->__requires_recast()) {
            $result = $result->__recast();
          }*/
        }

        if ($primary_key_search) {
            $active_record_to_store = end($results);
            if ($active_record_to_store instanceof ActiveRecord) {
                SearchIndex::getInstance()
                ->put($this->model->getTableName(), end($this->conditions)->getValue(), $active_record_to_store);
            }
        }
        return $results;
    }


    public function execOne()
    {
        // Get all the corresponding items
        $results = $this->exec();

        // Return the first result. Yes, that is what reset() does. :|
        if (reset($results) !== false) {
            return reset($results);
        }
        return false;
    }

    public function count()
    {
        return count($this->exec());
    }
}
