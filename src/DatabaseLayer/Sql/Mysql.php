<?php
namespace Thru\ActiveRecord\DatabaseLayer\Sql;

use Thru\ActiveRecord\DatabaseLayer\Exception;
use Thru\ActiveRecord\ActiveRecord;
use Thru\ActiveRecord\DatabaseLayer\IndexException;
use Thru\ActiveRecord\DatabaseLayer\TableBuildFailureException;
use Thru\ActiveRecord\DatabaseLayer\TableDestroyFailureException;
use Thru\JsonPrettyPrinter\JsonPrettyPrinter;
use Thru\UUID;

class Mysql extends Base
{

    private $known_indexes;

    /**
     * Turn a VirtualQuery into a SQL statement
     * @param \Thru\ActiveRecord\DatabaseLayer\VirtualQuery $thing
     * @return array of results
     * @throws Exception
     */
    public function process(\Thru\ActiveRecord\DatabaseLayer\VirtualQuery $thing)
    {

        switch ($thing->getOperation()) {
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
    public function processPassthru(\Thru\ActiveRecord\DatabaseLayer\Passthru $thing)
    {
        $sql    = $thing->get_sql_to_passthru();
        $result = $this->query(
            $sql,
            $thing->getModel()
        );

        // TODO: Make this a Collection.

        $results = [];
        if ($result !== false) {
            foreach ($result as $result_item) {
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
    public function processSelect(\Thru\ActiveRecord\DatabaseLayer\Select $thing)
    {
        $fields = [];
        $tables = [];
        $orders = [];

        // SELECTORS
        foreach ($thing->getTables() as $table) {
            /* @var $table \Thru\ActiveRecord\DatabaseLayer\Table */
            $tables[] = $table->getName() . " " . $table->getAlias();
            foreach ($table->getFields() as $field) {
                $fields[] = $table->getAlias() . "." . $field;
            }
        }
        $selector = "SELECT " . implode(" ", $fields);
        $from     = "FROM " . implode(" ", $tables);

        $conditions = $this->processConditions($thing);

        // Handle LIMIT & OFFSET
        $limit  = '';
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
                /* @var $order \Thru\ActiveRecord\DatabaseLayer\Order */
                $column = $order->getColumn();
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
                    $column    = '';
                    $direction = 'rand()';
                    break;
                default:
                    throw new Exception("Bad ORDER direction: {$order->getDirection()}");
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

        // echo " *** " . str_replace("\n", " ", $query) . "\n";

        $delay  = microtime(true);
        $result = $this->query($query, $thing->getModel());
        $delay  = microtime(true) - $delay;

        // TODO: Make this a Collection.

        $results = [];
        if ($result !== false) {
            foreach ($result as $result_item) {
                $results[] = $result_item;
            }
        }

        return $results;
    }

    public function processDelete(\Thru\ActiveRecord\DatabaseLayer\Delete $thing)
    {
        // SELECTORS
        if (count($thing->getTables()) > 1) {
            throw new Exception("Active Record Cannot delete from more than one table at a time!");
        }
        $tables = $thing->getTables();
        $table  = end($tables);

        $selector = "DELETE FROM {$table->getName()} ";

        $conditions = $this->processConditions($thing);

        $query = "{$selector}\n{$conditions}";

        $result = $this->query($query);

        return true;
    }

    // TODO: For the love of god, rewrite this to use PDO prepared statements
    public function processInsert(\Thru\ActiveRecord\DatabaseLayer\Insert $thing)
    {
        // SELECTORS
        if (count($thing->getTables()) > 1) {
            throw new Exception("Active Record Cannot insert into more than one table at a time!");
        }
        $tables = $thing->getTables();
        $table  = end($tables);

        $updates = [];
        foreach ($thing->getData() as $key => $value) {
            $key = trim($key, "`");
            if (is_object($value) || is_array($value)) {
                $value = JsonPrettyPrinter::Json($value);
            }
            $value_slashed = addslashes($value);
            if ($value === null) {
                $updates[] = "`$key` = NULL";
            } else {
                $updates[] = "`$key` = \"$value_slashed\"";
            }
        }
        $selector = "INSERT INTO {$table->getName()} ";
        $data     = "SET " . implode(", ", $updates);

        $query = "{$selector}\n{$data}";

        $this->query($query);

        if ($this->errorCode() !== '00000') {
            $info = $this->errorInfo();
            throw new \exception($info[2] . " -> {$query}");
        }
        $insertId = $this->lastInsertId();

        return $insertId;
    }

    public function processUpdate(\Thru\ActiveRecord\DatabaseLayer\Update $thing)
    {
        // SELECTORS
        if (count($thing->getTables()) > 1) {
            throw new Exception("Active Record Cannot update into more than one table at a time!");
        }
        $tables = $thing->getTables();
        $table  = end($tables);

        $updates = [];
        foreach ($thing->getData() as $key => $value) {
            $key = trim($key, "`");
            if (is_object($value) || is_array($value)) {
                $value = JsonPrettyPrinter::Json($value);
            }
            $value_slashed = addslashes($value);
            if ($value === null) {
                $updates[] = "`$key` = NULL";
            } else {
                $updates[] = "`$key` = \"$value_slashed\"";
            }
        }
        $selector = "UPDATE {$table->getName()} ";
        $data     = "SET " . implode(", ", $updates);

        $conditions = $this->processConditions($thing);

        $query = "{$selector}\n$data\n{$conditions}";
        //header("Content-type: text/plain"); echo $query; exit;

        $result = $this->query($query);

        if ($result instanceof \PDOStatement) {
            return $result->errorCode() == "00000" ? true : false;
        } else {
            return false;
        }
    }

    public function getIndexes($table)
    {
        if (isset($this->known_indexes[$table])) {
            return $this->known_indexes[$table];
        }
        $query   = "SHOW INDEX FROM {$table} WHERE Key_name = 'PRIMARY'";
        $indexes = $this->query($query);

        $results = [];
        if (!$indexes instanceof \PDOStatement) {
            $indexException         = new IndexException("Error running query: {$query}");
            $indexException->remedy = 'table_missing';
            throw $indexException;
        }
        if ($indexes->rowCount() > 0) {
            foreach ($indexes as $index) {
                $result              = new \StdClass();
                $result->Column_name = $index->Column_name;
                $results[]           = $result;
            }
        }
        $this->known_indexes[$table] = $results;

        return $results;
    }

    public function destroyTable(ActiveRecord $model)
    {
        $query = "DROP TABLE {$model->get_table_name()};";
        $this->query($query);
    }

    public function buildTable(ActiveRecord $model)
    {
        $schema = $model->get_class_schema();
        $params = [];
        foreach ($model->_calculate_save_down_rows() as $p => $parameter) {
            $auto_increment          = false;
            $type                    = "varchar(200)";
            $auto_increment_possible = false;

            if (isset($schema[$parameter])) {
                $psuedo_type = $schema[$parameter]['type'];
                switch (strtolower($psuedo_type)) {
                case 'int':
                case 'integer':
                    $length                  = isset($schema[$parameter]['length']) ? $schema[$parameter]['length'] : 10;
                    $type                    = "INT({$length})";
                    $auto_increment_possible = true;
                    break;

                case 'string':
                    $length = isset($schema[$parameter]['length']) ? $schema[$parameter]['length'] : 200;
                    $type   = "VARCHAR({$length})";
                    break;

                case 'date':
                case 'datetime':
                    $type = 'DATETIME';
                    break;

                case 'enum':
                    $type = "ENUM('" . implode("', '", $schema[$parameter]['options']) . "')";
                    break;

                case 'text':
                    $type = "TEXT";
                    break;

                case 'blob':
                    $type = 'BLOB';
                    break;

                case "decimal":
                    $type = "DECIMAL(" . implode(",", $schema[$parameter]['options']) . ")";
                    break;

                case "uuid":
                    $type = "VARCHAR(" . strlen(UUID::v4()) . ")";
                    break;

                case "md5":
                    $type = "VARCHAR(" . strlen(md5("test")) . ")";
                    break;

                case "sha1":
                    $type = "VARCHAR(" . strlen(sha1("test")) . ")";
                    break;
                }
            }

            if ($p == 0 && $auto_increment_possible) {
                // First param always primary key if possible
                $primary_key    = $parameter;
                $auto_increment = true;
            }
            if ($auto_increment) {
                $auto_increment_sql = 'AUTO_INCREMENT';
            } else {
                $auto_increment_sql = '';
            }
            $nullability = "NOT NULL";
            $params[]    = "  " . trim("`{$parameter}` {$type} {$nullability} {$auto_increment_sql}");
        }
        if (isset($primary_key)) {
            $params[] = "  PRIMARY KEY (`$primary_key`)";
        }

        $query = "CREATE TABLE IF NOT EXISTS `{$model->get_table_name()}`\n";
        $query .= "(\n";
        $query .= implode(",\n", $params) . "\n";
        $query .= ")\n";
        $query .= "ENGINE=InnoDB DEFAULT CHARSET=UTF8\n";

        $this->query($query);
    }

    private function processConditions($thing)
    {
        // CONDITIONS
        if (count($thing->getConditions()) > 0) {
            foreach ($thing->getConditions() as $condition) {
                /* @var $condition \Thru\ActiveRecord\DatabaseLayer\Condition */
                if ($condition->getOperation() == "IN" || is_array($condition->getValue()) && $condition->getOperation() == '=') {
                    $conditions[] = "`{$condition->getColumn()}` IN(\"" . implode('", "', $condition->getValue()) . "\")";
                } elseif ($condition->getOperation() == "NOT IN" || is_array($condition->getValue()) && $condition->getOperation() == '!=') {
                    $conditions[] = "`{$condition->getColumn()}` NOT IN(\"" . implode('", "', $condition->getValue()) . "\")";
                } else {
                    $conditions[] = "`{$condition->getColumn()}` {$condition->getOperation()} \"{$condition->getValue()}\"";
                }
            }
            $conditions = "WHERE " . implode("\n  AND ", $conditions);
        } else {
            $conditions = null;
        }

        return $conditions;
    }
}
