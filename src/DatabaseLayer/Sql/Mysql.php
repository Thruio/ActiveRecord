<?php
namespace Thru\ActiveRecord\DatabaseLayer\Sql;

use Monolog\Logger;
use Thru\ActiveRecord\DatabaseLayer;
use Thru\ActiveRecord\DatabaseLayer\Exception;
use Thru\ActiveRecord\ActiveRecord;
use Thru\ActiveRecord\DatabaseLayer\IndexException;
use Thru\ActiveRecord\VersionedActiveRecord;
use Thru\JsonPrettyPrinter\JsonPrettyPrinter;
use Thru\UUID;

class Mysql extends GenericSql
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

        $updates = array();
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
        $data = "SET " . implode(", ", $updates);

        $query = "{$selector}\n{$data}";

        $this->query($query);

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
            $value_slashed = addslashes($value);
            if ($value === null) {
                $updates[] = "`$key` = NULL";
            } else {
                $updates[] = "`$key` = \"$value_slashed\"";
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
        $query = "SHOW COLUMNS FROM {$table} WHERE `Key` = 'PRI'";
        $indexes = $this->query($query);

        $results = array();
        if (!$indexes instanceof \PDOStatement) {
            $indexException = new IndexException("Error running query: {$query}");
            $indexException->remedy = 'table_missing';
            throw $indexException;
        }
        if ($indexes->rowCount() > 0) {
            foreach ($indexes as $index) {
                $result = new \StdClass();
                $result->Column_name = $index->Field;
                $result->Auto_increment = stripos($index->Extra, "auto_increment")!==false?true:false;
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
                        $length = isset($schema[$parameter]['length']) ? $schema[$parameter]['length'] : 10;
                        $type = "INT({$length})";
                        $auto_increment_possible = true;
                        break;

                    case 'string':
                        $length = isset($schema[$parameter]['length']) ? $schema[$parameter]['length'] : 200;
                        $type = "VARCHAR({$length})";
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

            if ($p == 0) {
                // First param always primary key if possible
                if ($auto_increment_possible) {
                    $primary_key = $parameter;
                    if (!$model instanceof VersionedActiveRecord) {
                        $auto_increment = true;
                    }
                }
            }
            if ($auto_increment) {
                $auto_increment_sql = 'AUTO_INCREMENT';
            } else {
                $auto_increment_sql = '';
            }
            $nullability = $schema[$parameter]['nullable'] ? "NULL" : "NOT NULL";
            $params[] = "  " . trim("`{$parameter}` {$type} {$nullability} {$auto_increment_sql}");
        }

        // Disable auto-increment if this object is versioned.
        if ($model instanceof VersionedActiveRecord) {
            if (isset($primary_key)) {
                $params[] = "  PRIMARY KEY (`$primary_key`, `sequence`)";
            }
        } else {
            if (isset($primary_key)) {
                $params[] = "  PRIMARY KEY (`$primary_key`)";
            }
        }

        $query = "CREATE TABLE IF NOT EXISTS `{$model->getTableName()}`\n";
        $query.= "(\n";
        $query.= implode(",\n", $params)."\n";
        $query.= ")\n";
        $query.= "ENGINE=InnoDB DEFAULT CHARSET=UTF8\n";

        // Log it.
        if (DatabaseLayer::getInstance()->getLogger() instanceof Logger) {
            DatabaseLayer::getInstance()->getLogger()->addInfo("Creating table {$model->getTableName()}\n\n{$query}");
        }

        $this->query($query);
    }
}
