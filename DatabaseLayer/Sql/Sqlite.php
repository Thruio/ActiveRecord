<?php
namespace FourOneOne\ActiveRecord\DatabaseLayer\Sql;

use FourOneOne\ActiveRecord\DatabaseLayer\Exception;

class Sqlite extends Base
{

    /**
     * Turn a VirtualQuery into a SQL statement
     * @param \FourOneOne\ActiveRecord\DatabaseLayer\VirtualQuery $thing
     * @return array of results
     * @throws Exception
     */
    public function process(\FourOneOne\ActiveRecord\DatabaseLayer\VirtualQuery $thing)
    {
        $fields = array();
        $tables = array();
        $conditions = array();
        $orders = array();

        // SELECTORS
        foreach ($thing->getTables() as $table) {
            /* @var $table \FourOneOne\ActiveRecord\DatabaseLayer\Table */
            $tables[] = $table->getName() . " " . $table->getAlias();
            foreach ($table->getFields() as $field) {
                $fields[] = $table->getAlias() . "." . $field;
            }
        }
        $selector = "SELECT " . implode(" ", $fields);
        $from = "FROM " . implode(" ", $tables);

        // CONDITIONS
        if(count($thing->getConditions()) > 0){
            foreach($thing->getConditions() as $condition){
                /* @var $condition \FourOneOne\ActiveRecord\DatabaseLayer\Condition */
                if($condition->getOperation() == "IN"){
                    $conditions[] = "`{$condition->getColumn()}` IN(\"" . implode('", "', $condition->getValue()) . "\")";
                }else{
                    $conditions[] = "`{$condition->getColumn()}` {$condition->getOperation()} \"{$condition->getValue()}\"";
                }
            }
            $conditions = "WHERE " . implode("\n  AND ", $conditions);
        }else{
            $conditions = null;
        }

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
                /* @var $order \FourOneOne\ActiveRecord\DatabaseLayer\Order */
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
                        $direction = 'RANDOM()';
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
        //header("Content-type: text/plain"); echo $query; exit;

        $result = $this->query($query, $thing->getModel());

        // TODO: Make this a Collection.
        $results = array();
        foreach($result as $result_item){
            $results[] = $result_item;
        }

        return $results;
    }

    public function getIndexes($table){
        $indexes = $this->query("PRAGMA table_info('{$table}')");
        $results = array();
        foreach($indexes as $index){
            if($index->pk == 1){
                $result = new \StdClass();
                $result->Column_name = $index->name;
                $results[] = $result;
            }

        }
        return $results;
    }


}
