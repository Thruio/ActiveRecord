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

class GenericSql extends Base
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

        #echo "*** process() model is " . $thing->getModel()."\n";
        switch ($thing->getOperation()) {
            case 'Insert':
                //Create
                return $this->processInsert($thing);
            case 'Select':
                //Read
                return $this->processSelect($thing);
            case 'Update':
                //Update
                return $this->processUpdate($thing);
            case 'Delete':
                //Delete
                return $this->processDelete($thing);
            case 'Passthru':
                //Delete
                return $this->processPassthru($thing);
            default:
                throw new Exception("Operation {$thing->getOperation()} not supported");
        }
    }

    public function processDelete(DatabaseLayer\Delete $thing)
    {
        // SELECTORS
        if (count($thing->getTables()) > 1) {
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

    protected function processConditions($thing)
    {
        $conditions = [];
        // CONDITIONS
        if (count($thing->getConditions()) > 0) {
            foreach ($thing->getConditions() as $condition) {
                /* @var $condition DatabaseLayer\Condition */
                if ($condition->getOperation() == "IN" || is_array($condition->getValue()) && $condition->getOperation() == '=') {
                    $conditions[] = "`{$condition->getColumn()}` IN(\"" . implode('", "',
                        $condition->getValue()) . "\")";
                } elseif ($condition->getOperation() == "NOT IN" || is_array($condition->getValue()) && $condition->getOperation() == '!=') {
                    $conditions[] = "`{$condition->getColumn()}` NOT IN(\"" . implode('", "',
                        $condition->getValue()) . "\")";
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

    /**
     * @param \Thru\ActiveRecord\DatabaseLayer\Passthru $thing
     * @return array
     * @throws \Thru\ActiveRecord\DatabaseLayer\Exception
     */
    public function processPassthru(DatabaseLayer\Passthru $thing)
    {
        $sql = $thing->get_sql_to_passthru();
        $result = $this->query($sql, $thing->getModel());

        // TODO: Make this a Collection.

        $results = array();
        if ($result !== false && $result !== null) {
            foreach ($result as $result_item) {
                $results[] = $result_item;
            }
        }

        return $results;
    }
}