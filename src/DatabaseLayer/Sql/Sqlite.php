<?php
namespace Thru\ActiveRecord\DatabaseLayer\Sql;

use Monolog\Logger;
use Thru\ActiveRecord\ActiveRecord;
use Thru\ActiveRecord\DatabaseLayer;
use Thru\ActiveRecord\DatabaseLayer\Exception;
use Thru\ActiveRecord\DatabaseLayer\IndexException;
use Thru\ActiveRecord\VersionedActiveRecord;
use Thru\JsonPrettyPrinter\JsonPrettyPrinter;

class Sqlite extends GenericSql
{
    /**
     * @param \Thru\ActiveRecord\DatabaseLayer\Select $thing
     * @return array
     * @throws \Thru\ActiveRecord\DatabaseLayer\Exception
     */
    public function processSelect(DatabaseLayer\Select $thing)
    {
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
        if ($thing->getLimit()) {
            $limit = "LIMIT {$thing->getLimit()}";
            if ($thing->getOffset()) {
                $offset = "OFFSET {$thing->getOffset()}";
            }
        }

        // Handle ORDERs
        if (count($thing->getOrders()) > 0) {
            foreach ($thing->getOrders() as $order) {
                /* @var $order DatabaseLayer\Order */
                $column = $order->getColumn();
                if (in_array($order->getColumn(), ['rand', 'rand()', 'random()', 'random'])) {
                    $column = 'RANDOM()';
                    $direction = '';
                } else {
                    switch (strtolower($order->getDirection())) {
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
                            $direction = '';
                            break;
                        default:
                            throw new Exception("Bad ORDER direction: {$order->getDirection()}");
                    }
                }

                $orders[] = $column . " " . $direction;
            }
        }
        if (count($orders) > 0) {
            $order = "ORDER BY " . implode(", ", $orders);
        } else {
            $order = null;
        }

        $query = "{$selector}\n{$from}\n{$conditions}\n{$order}\n{$limit} {$offset}";


        $delay = microtime(true);
        $result = $this->query($query, $thing->getModel());
        $delay = microtime(true) - $delay;


        // TODO: Make this a Collection.

        $results = array();
        if ($result !== false) {
            foreach ($result as $result_item) {
                $results[] = $result_item;
            }
        }
        #\Kint::dump(DatabaseLayer::getInstance()->getOption('db_file'), $query, $results);

        return $results;
    }


    // TODO: For the love of god, rewrite this to use PDO prepared statements
    public function processInsert(DatabaseLayer\Insert $thing)
    {
        // SELECTORS
        if (count($thing->getTables()) > 1) {
            throw new Exception("Active Record Cannot insert into more than one table at a time!");
        }
        $tables = $thing->getTables();
        $table = end($tables);

        $data = $thing->getData();

        $keys = [];
        $values = [];

        foreach ($data as $key => $value) {
            $key = trim($key, "`");
            if (is_object($value) || is_array($value)) {
                $value = JsonPrettyPrinter::Json($value);
            }
            $keys[] = $key;

            $value_slashed = str_replace("'", "''", $value);
            if ($value === null) {
                $value = "NULL";
            } elseif (is_numeric($value)) {
                // Do nothing
            } else {
                $value = "'{$value_slashed}'";
            }
            $values[] = $value;
        }
        $selector = "INSERT INTO {$table->getName()} ";
        $columns = "(`" . implode("`, `", $keys) . "`)";
        $values = "(" . implode(", ", $values) . ")";
        $query = "{$selector}\n{$columns} \nVALUES \n{$values}";

        // echo "*** Just before query(): ".$thing->getModel() . "\n";
        $this->query($query, $thing->getModel());

        $insertId = $this->lastInsertId();

        return $insertId;
    }

    public function processUpdate(DatabaseLayer\Update $thing)
    {
        // SELECTORS
        if (count($thing->getTables()) > 1) {
            throw new Exception("Active Record Cannot update into more than one table at a time!");
        }
        $tables = $thing->getTables();
        $table = end($tables);

        $updates = array();
        foreach ($thing->getData() as $key => $value) {
            $key = trim($key, "`");
            if (is_object($value) || is_array($value)) {
                $value = JsonPrettyPrinter::Json($value);
            }
            $value_slashed = str_replace("',", "''", $value);
            if ($value === null) {
                $updates[] = "`{$key}` = NULL";
            } elseif (is_numeric($value)) {
                $updates[] = "`{$key}` = {$value_slashed}";
            } else {
                $updates[] = "`{$key}` = '{$value_slashed}'";
            }
        }
        $selector = "UPDATE {$table->getName()} ";
        $data = "SET " . implode(", ", $updates);

        $conditions = $this->processConditions($thing);

        $query = "{$selector}\n$data\n{$conditions}";
        //header("Content-type: text/plain"); echo $query; exit;

        $result = $this->query($query);

        return $result->errorCode() == "00000" ? true : false;
    }

    public function getIndexes($table)
    {
        if (isset($this->known_indexes[$table])) {
            return $this->known_indexes[$table];
        }

        $query = "PRAGMA table_info('{$table}')";
        $indexes = $this->query($query);

        $results = array();
        if (!$indexes instanceof \PDOStatement) {
            $indexException = new IndexException("Error running query: {$query}");
            $indexException->remedy = 'table_missing';
            throw $indexException;
        }
        $indexesResult = $indexes->fetchAll();

        foreach ($indexesResult as $index) {
            if ($index->pk == 1) {
                $result = new \StdClass();
                $result->Column_name = $index->name;
                $result->Auto_increment = true;
                $results[] = $result;
            }
        }
        $this->known_indexes[$table] = $results;

        return $results;
    }

    public function destroyTable(ActiveRecord $model)
    {
        $query = "DROP TABLE {$model->getTableName()};";
        $this->query($query);
    }

    public function buildTable(ActiveRecord $model)
    {
        $schema = $model->getClassSchema();
        $params = array();
        foreach ($model->__calculateSaveDownRows() as $p => $parameter) {
            $auto_increment = false;
            $type = "varchar(200)";
            $auto_increment_possible = false;

            if (isset($schema[$parameter])) {
                $psuedo_type = $schema[$parameter]['type'];
                switch (strtolower($psuedo_type)) {
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
            if ($p == 0) {
                // First param always primary key if possible
                if ($auto_increment_possible) {
                    $is_primary_key = "PRIMARY KEY";
                    if (!$model instanceof VersionedActiveRecord) {
                        $auto_increment = true;
                    }
                }
            }

            if ($auto_increment && !$model instanceof VersionedActiveRecord) {
                $auto_increment_sql = 'AUTOINCREMENT';
            } else {
                $auto_increment_sql = false;
            }

            $nullability = $schema[$parameter]['nullable'] ? "NULL" : "NOT NULL";
            $nullability = $is_primary_key ? '' : $nullability;

            $is_primary_key = !$model instanceof VersionedActiveRecord ? $is_primary_key : null;

            $params[] = "  " . trim("`{$parameter}` {$type} {$is_primary_key} {$auto_increment_sql} {$nullability}");
        }

        $query = "CREATE TABLE IF NOT EXISTS `{$model->getTableName()}`\n";
        $query .= "(\n";
        $query .= implode(",\n", $params) . "\n";
        $query .= ")\n";

        $this->query($query);

        // Log it.
        if (DatabaseLayer::getInstance()->getLogger() instanceof Logger) {
            DatabaseLayer::getInstance()->getLogger()->addInfo("Creating table {$model->getTableName()}\n\n{$query}");
        }
    }

    public function query($query, $model = 'StdClass')
    {
        try {
            $result = parent::query($query, $model);
            $error = parent::errorInfo();
            if($error['2'] == 'database_is_locked'){
                throw new DatabaseLayer\LockingException("Database is locked.");
            }
            return $result;
        } catch (DatabaseLayer\TableDoesntExistException $tdee) {
            echo "Caught";
            if (stripos($tdee->getMessage(), "HY000") !== false) {
                if (stripos($tdee->getMessage(), "no such table") !== false) {
                    $table = str_replace("HY000: SQLSTATE[HY000]: General error: 1 no such table: ", "",
                      $tdee->getMessage());
                    throw new DatabaseLayer\TableDoesntExistException("42S02: SQLSTATE[42S02]: Base table or view not found: 1051 Unknown table '{$table}'",
                      $tdee->getCode(), $tdee);
                }
            }
            throw $tdee;
        }
    }
}
