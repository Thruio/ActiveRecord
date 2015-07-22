<?php

namespace Thru\ActiveRecord\DatabaseLayer\Sql;

use Thru\ActiveRecord\DatabaseLayer;
use Thru\ActiveRecord\DatabaseLayer\Exception;

abstract class GenericSql extends Base
{
    protected $known_indexes;

    /**
     * Turn a VirtualQuery into a SQL statement
     * @param \Thru\ActiveRecord\DatabaseLayer\VirtualQuery $thing
     * @return array|boolean
     * @throws Exception
     */
    public function process(DatabaseLayer\VirtualQuery $thing)
    {
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

    abstract public function processInsert(DatabaseLayer\Insert $thing);

    abstract public function processSelect(DatabaseLayer\Select $thing);

    abstract public function processUpdate(DatabaseLayer\Update $thing);

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

        $this->query($query, $thing->getModel());

        return true;
    }

    /**
     * @param \Thru\ActiveRecord\DatabaseLayer\Passthru $thing
     * @return array
     * @throws \Thru\ActiveRecord\DatabaseLayer\Exception
     */
    public function processPassthru(DatabaseLayer\Passthru $thing)
    {
        $sql = $thing->getSqlToPassthru();
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

    protected function processConditions($thing)
    {
        $conditions = [];
        // CONDITIONS
        if (count($thing->getConditions()) > 0) {
            foreach ($thing->getConditions() as $condition) {
                /* @var $condition DatabaseLayer\Condition */
                if ($condition->getOperation() == "IN" || is_array($condition->getValue()) && $condition->getOperation() == '=') {
                    $conditions[] = "`{$condition->getColumn()}` IN(\"" . implode(
                        '", "',
                        $condition->getValue()
                    ) . "\")";
                } elseif ($condition->getOperation() == "NOT IN" || is_array($condition->getValue()) && $condition->getOperation() == '!=') {
                    $conditions[] = "`{$condition->getColumn()}` NOT IN(\"" . implode(
                        '", "',
                        $condition->getValue()
                    ) . "\")";
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
