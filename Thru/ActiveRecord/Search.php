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

    public function condition(SearchCondition $condition){
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
        $primary_key_search = FALSE;
        if (count($this->conditions) == 1) {
          /* @var $model ActiveRecord */
          $model = $this->model;

          if (end($this->conditions)->get_column() == $model->get_table_primary_key() && end($this->conditions)->get_operation() == '=') {
            $primary_key_search = TRUE;
            if (SearchIndex::get_instance()->exists($model->get_table_name(), end($this->conditions)->get_value())) {
              return array(
                SearchIndex::get_instance()
                  ->get($model->get_table_name(), end($this->conditions)->get_value())
              );
            }
          }
          unset($model);
        }

        $database = DatabaseLayer::get_instance();

        $select = $database->select($this->model->get_table_name(), $this->model->get_table_alias());
        $select->fields($this->model->get_table_alias());

        // Add WHERE Conditions
        foreach ((array)$this->conditions as $condition) {
          $select->condition($condition->get_column(), $condition->get_value(), $condition->get_operation());
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

    private function execProcessResponse($response, $primary_key_search){
      $results = array();

      foreach ($response as $result) {
        /* @var $result ActiveRecord */
        if ($result->get_primary_key_index()) {
          $primary_key_column = $result->get_primary_key_index();
          $results[$result->$primary_key_column] = $result;
        } else {
          $results[] = $result;
        }
      }

      var_dump($results);

      foreach($results as $result){
        $result->field_fix();
      }

      // Check for ActiveRecord_class and recast as needed
      /*foreach ($results as $key => $result) {
        $results[$key] = $result->__recast();
      }*/

      // Call __post_construct on each of the newly constructed objects.
      foreach ($results as &$result) {
        /* @var $result ActiveRecord */
        $result->__post_construct();
        /*if ($result->__requires_recast()) {
          $result = $result->__recast();
        }*/
      }

      if ($primary_key_search) {
        $active_record_to_store = end($results);
        if ($active_record_to_store instanceof ActiveRecord) {
          SearchIndex::get_instance()
            ->put($this->model->get_table_name(), end($this->conditions)->get_value(), $active_record_to_store);
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
        return FALSE;
    }

    public function count()
    {
        return count($this->exec());
    }
}