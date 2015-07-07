<?php
namespace Thru\ActiveRecord\DatabaseLayer\Sql;

use Monolog\Logger;
use SebastianBergmann\Version;
use Thru\ActiveRecord\DatabaseLayer;
use Thru\ActiveRecord\DatabaseLayer\Exception;
use Thru\ActiveRecord\ActiveRecord;
use Thru\ActiveRecord\DatabaseLayer\IndexException;
use Thru\ActiveRecord\VersionedActiveRecord;
use Thru\JsonPrettyPrinter\JsonPrettyPrinter;
use Thru\UUID;

class Sqlite extends Base
{

    private $known_indexes;

    /**
     * Turn a VirtualQuery into a SQL statement
     * @param \Thru\ActiveRecord\DatabaseLayer\VirtualQuery $thing
     * @return array of results
     * @throws Exception
     */
    public function process(DatabaseLayer\VirtualQuery $thing)
    {

        switch($thing->getOperation()){
            case 'Insert': //Create
                return $this->processInsert($thing);
            case 'Select': //Read
                return $this->processSelect($thing);
            case 'Update': //Update
                return $this->processUpdate($thing);
            case 'Delete': //Delete
                return $this->processDelete($thing);
            case 'Passthru': //Delete
                return $this->processPassthru($thing);
            default:
                throw new Exception("Operation {$thing->getOperation()} not supported");
        }
    }

    /**
     * @param \Thru\ActiveRecord\DatabaseLayer\Passthru $thing
     * @return array
     * @throws \Thru\ActiveRecord\DatabaseLayer\Exception
     */
    public function processPassthru(DatabaseLayer\Passthru $thing){
      $sql = $thing->get_sql_to_passthru();
      $result = $this->query(
        $sql,
        $thing->getModel()
      );

      // TODO: Make this a Collection.

      $results = array();
      if($result !== false && $result !== null){
        foreach($result as $result_item){
          $results[] = $result_item;
        }
      }

      return $results;
    }

    /**
     * @param \Thru\ActiveRecord\DatabaseLayer\Select $thing
     * @return array
     * @throws \Thru\ActiveRecord\DatabaseLayer\Exception
     */
    public function processSelect(DatabaseLayer\Select $thing){
        $fields = array();
        $tables = array();
        $orders = array();

        // SELECTORS
        foreach ($thing->getTables() as $table) {
            /* @var $table DatabaseLayer\Table */
            $tables[] = $table->getName() . " " . $table->getAlias();
            foreach ($table->getFields() as $field) {
                $fields[] = $table->getAlias() . "." . $field;
            }
        }
        $selector = "SELECT " . implode(" ", $fields);
        $from = "FROM " . implode(" ", $tables);

      $conditions = $this->processConditions($thing);

      // Handle LIMIT & OFFSET
        $limit = '';
        $offset = '';
        if($thing->getLimit()){
            $limit = "LIMIT {$thing->getLimit()}";
            if($thing->getOffset()){
                $offset = "OFFSET {$thing->getOffset()}";
            }
        }

        // Handle ORDERs
        if(count($thing->getOrders()) > 0){
            foreach($thing->getOrders() as $order){
                /* @var $order DatabaseLayer\Order */
                $column = $order->getColumn();
                switch(strtolower($order->getDirection())){
                    case 'asc':
                    case 'ascending':
                        $direction = 'ASC';
                        break;
                    case 'desc':
                    case 'descending':
                        $direction = 'DESC';
                        break;
                    case 'rand()':
                    case 'rand':
                    case 'random()':
                    case 'random':
                        $column = '';
                        $direction = 'rand()';
                        break;
                    default:
                        throw new Exception("Bad ORDER direction: {$order->getDirection()}");
                }

                $orders[] = $column . " " . $direction;
            }
        }
        if(count($orders) > 0){
            $order = "ORDER BY " . implode(", ", $orders);
        }else{
            $order = null;
        }

        $query = "{$selector}\n{$from}\n{$conditions}\n{$order}\n{$limit} {$offset}";

        #echo " *** " . str_replace("\n", " ", $query) . "\n";

        $delay = microtime(true);
        $result = $this->query($query, $thing->getModel());
        $delay = microtime(true) - $delay;

        // TODO: Make this a Collection.

        $results = array();
        if($result !== false){
            foreach($result as $result_item){
                $results[] = $result_item;
            }
        }

        return $results;
    }

    public function processDelete(DatabaseLayer\Delete $thing){
        // SELECTORS
        if(count($thing->getTables()) > 1){
          throw new Exception("Active Record Cannot delete from more than one table at a time!");
        }
        $tables = $thing->getTables();
        $table = end($tables);

        $selector = "DELETE FROM {$table->getName()} ";

        $conditions = $this->processConditions($thing);

        $query = "{$selector}\n{$conditions}";

        $result = $this->query($query, $thing->getModel());

        return true;
    }

    // TODO: For the love of god, rewrite this to use PDO prepared statements
    public function processInsert(DatabaseLayer\Insert $thing){
        // SELECTORS
        if(count($thing->getTables()) > 1){
            throw new Exception("Active Record Cannot insert into more than one table at a time!");
        }
        $tables = $thing->getTables();
        $table = end($tables);

        $updates = array();
        $keys = [];
        $values = [];
        foreach($thing->getData() as $key => $value){
            $key = trim($key,"`");
            if(is_object($value) || is_array($value)){
                $value = JsonPrettyPrinter::Json($value);
            }
            $keys[] = $key;

            $value_slashed = addslashes($value);
            if($value === null){
                $value = "NULL";
            }else{
                $value = $value_slashed;
            }
            $values[] = $value;
        }
        $selector = "INSERT INTO {$table->getName()} ";
        $columns = "(`" . implode("`, `", $keys) . "`)";
        $values = "('" . implode("', '", $values) . "')";

        $query = "{$selector}\n{$columns} \nVALUES \n{$values}";

        $this->query($query);

        $insertId = $this->lastInsertId();

        return $insertId;
    }

    public function processUpdate(DatabaseLayer\Update $thing){
        // SELECTORS
        if(count($thing->getTables()) > 1){
            throw new Exception("Active Record Cannot update into more than one table at a time!");
        }
        $tables = $thing->getTables();
        $table = end($tables);

        $updates = array();
        foreach($thing->getData() as $key => $value){
            $key = trim($key,"`");
            if(is_object($value) || is_array($value)){
                $value = JsonPrettyPrinter::Json($value);
            }
            $value_slashed = addslashes($value);
            if($value === null){
                $updates[] = "`$key` = NULL";
            }else{
                $updates[] = "`$key` = \"$value_slashed\"";
            }
        }
        $selector = "UPDATE {$table->getName()} ";
        $data = "SET " . implode(", ", $updates);

        $conditions = $this->processConditions($thing);

        $query = "{$selector}\n$data\n{$conditions}";
        //header("Content-type: text/plain"); echo $query; exit;

        $result = $this->query($query);

        return $result->errorCode() == "00000" ? TRUE : FALSE;
    }

    public function getIndexes($table){
        if(isset($this->known_indexes[$table])){
          return $this->known_indexes[$table];
        }

        $query = "PRAGMA table_info('{$table}')";
        $indexes = $this->query($query);

        $results = array();
        if(!$indexes instanceof \PDOStatement){
          $indexException = new IndexException("Error running query: {$query}");
          $indexException->remedy = 'table_missing';
          throw $indexException;
        }
        if($indexes->rowCount() > 0){
            foreach($indexes as $index){
              if($index->pk==1){
                $result = new \StdClass();
                $result->Column_name = $index->name;
                $result->Auto_increment = true;
                $results[] = $result;
              }
            }
        }
        $this->known_indexes[$table] = $results;
        return $results;
    }

    public function destroyTable(ActiveRecord $model){
      $query = "DROP TABLE {$model->get_table_name()};";
      $this->query($query);
    }

    public function buildTable(ActiveRecord $model){
        $schema = $model->get_class_schema();
        $params = array();
        foreach($model->_calculate_save_down_rows() as $p => $parameter){
            $auto_increment = false;
            $type = "varchar(200)";
            $auto_increment_possible = false;

            if(isset($schema[$parameter])){
              $psuedo_type = $schema[$parameter]['type'];
              switch(strtolower($psuedo_type)){
                case 'int':
                case 'integer':
                  $type = "INTEGER";
                  $auto_increment_possible = true;
                  break;

                case 'date':
                case 'datetime':
                case 'enum':
                case 'string':
                case 'text':
                case 'uuid':
                case 'md5':
                case 'sha1':
                  $type = "TEXT";
                  break;

                case 'blob':
                  $type = 'BLOB';
                  break;

              }
            }

            $is_primary_key = false;
            if($p == 0){
                // First param always primary key if possible
                if($auto_increment_possible) {
                  $is_primary_key = "PRIMARY KEY";
                  if(!$model instanceof VersionedActiveRecord) {
                    $auto_increment = true;
                  }
                }
            }

            if($auto_increment){
              $auto_increment_sql = 'AUTOINCREMENT';
            }else{
              $auto_increment_sql = false;
            }

            $nullability = $schema[$parameter]['nullable'] ? "NULL" : "NOT NULL";
            $nullability = $is_primary_key?'':$nullability;

            $params[] = "  " . trim("`{$parameter}` {$type} {$is_primary_key} {$auto_increment_sql} {$nullability}");
        }

        // TODO: Disable auto-increment if this object is versioned
        $query = "CREATE TABLE IF NOT EXISTS `{$model->get_table_name()}`\n";
        $query.= "(\n";
        $query.= implode(",\n", $params)."\n";
        $query.= ")\n";

        // Log it.
        if(DatabaseLayer::get_instance()->getLogger() instanceof Logger) {
          DatabaseLayer::get_instance()->getLogger()->addInfo("Creating table {$model->get_table_name()}\n\n{$query}");
        }

        $this->query($query);
    }

    private function processConditions($thing){
        // CONDITIONS
        if(count($thing->getConditions()) > 0){
            foreach($thing->getConditions() as $condition){
                /* @var $condition DatabaseLayer\Condition */
                if($condition->getOperation() == "IN" || is_array($condition->getValue()) && $condition->getOperation() == '='){
                    $conditions[] = "`{$condition->getColumn()}` IN(\"" . implode('", "', $condition->getValue()) . "\")";
                }elseif($condition->getOperation() == "NOT IN" || is_array($condition->getValue()) && $condition->getOperation() == '!='){
                    $conditions[] = "`{$condition->getColumn()}` NOT IN(\"" . implode('", "', $condition->getValue()) . "\")";
                }else{
                    $conditions[] = "`{$condition->getColumn()}` {$condition->getOperation()} \"{$condition->getValue()}\"";
                }
            }
            $conditions = "WHERE " . implode("\n  AND ", $conditions);
        }else{
            $conditions = null;
        }
        return $conditions;
    }
}
