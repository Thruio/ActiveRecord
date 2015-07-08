<?php
namespace Thru\ActiveRecord\DatabaseLayer;

class Select extends VirtualQuery
{

    public function __construct($table, $alias = null)
    {
        $this->tables[$alias] = new Table($table);
    }

    /**
     * @param $alias
     * @param string $fields
     * @return $this
     * @throws \Thru\ActiveRecord\Exception
     */
    public function fields($alias, $fields = '*')
    {
        if (!is_array($fields)) {
            $fields = array($fields);
        }
        if (isset($this->tables[$alias])) {
            $this->tables[$alias]->setFields($fields);
        } else {
            throw new Exception("No table matching alias '{$alias}' selectable");
        }
        return $this;
    }

    /**
     * @param integer $offset
     */
    public function range($offset, $limit)
    {
        $this->offset = $offset;
        $this->limit = $limit;
        return $this;
    }

    /**
     * @param string $direction
     */
    public function orderBy($column, $direction)
    {
        $this->addOrder(new Order($column, $direction));
        return $this;
    }
}
