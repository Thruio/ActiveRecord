<?php

namespace Thru\ActiveRecord;

use CodeClimate\Bundle\TestReporterBundle\Version;
use Thru\ActiveRecord\DatabaseLayer\TableBuilder;
use Thru\JsonPrettyPrinter\JsonPrettyPrinter;

abstract class ActiveRecord
{
    static public $MYSQL_FORMAT = "Y-m-d H:i:s";
    protected $_label_column = null;
    protected $_columns;
    protected $_table;

    /**
     * Start a Search on this type of active record
     * @return Search
     */
    public static function search()
    {
        $class = get_called_class();
        return new Search(new $class);
    }

    /**
     * Generic Factory constructor
     * @return ActiveRecord
     */
    public static function factory()
    {
        $name = get_called_class();
        return new $name();
    }

    /**
     * Override-able __construct call
     */
    public function __construct()
    {
        $tableBuilder = $this->getTableBuilder();
        $tableBuilder->build($this);
    }

    /**
     * Override-able calls
     */
    public function postConstruct()
    {
    }

    public function preSave()
    {
    }

    public function postSave()
    {
    }

    /**
     * Find an item by the Primary Key ID. This does not use the search() functionality
     * @param integer $id
     * @return ActiveRecord
     */
    public function getById($id)
    {
        $database = DatabaseLayer::getInstance();
        $select = $database->select($this->getTableName(), $this->getTableAlias());
        $select->fields($this->getTableAlias());
        $select->condition($this->getTablePrimaryKey(), $id);
        $results = $select->execute(get_called_class());
        $result = end($results);
        return $result;
    }

    /**
     * Get the short alias name of a table.
     *
     * @param string $table_name Optional table name
     * @return string Table alias
     */
    public function getTableAlias($table_name = null)
    {
        if (!$table_name) {
            $table_name = $this->getTableName();
        }
        $bits = explode("_", $table_name);
        $alias = '';
        foreach ($bits as $bit) {
            $alias .= strtolower(substr($bit, 0, 1));
        }
        return $alias;
    }

    /**
     * Get the table name
     *
     * @return string Table Name
     */
    public function getTableName()
    {
        return $this->_table;
    }

    /**
     * Get table primary key column name
     *
     * @deprecated
     * @return string|false
     */
    public function getTablePrimaryKey()
    {
        trigger_error('Use getPrimaryKeyIndex instead', E_USER_DEPRECATED);

        $keys = $this->getPrimaryKeyIndex();
        return isset($keys[0])?$keys[0]:false;
    }

    /**
     * Get a unique key to use as an index
     *
     * @return string[]
     */
    public function getPrimaryKeyIndex()
    {
        $database = DatabaseLayer::getInstance();

        $columns = array();

        if ($this instanceof VersionedActiveRecord) {
            $schema = $this->getClassSchema();
            $firstColumn = reset($schema)['name'];
            $columns = [$firstColumn => $firstColumn, "sequence" => "sequence"];
        } else {
            foreach ($database->getTableIndexes($this->_table) as $key) {
                $columns[$key->Column_name] = $key->Column_name;
            }
        }

        return array_values($columns);
    }

    /**
     * Get object ID
     * @return integer
     */
    public function getId()
    {

        $col = $this->getTablePrimaryKey();

        if (property_exists($this, $col)) {
            $id = $this->$col;
            if ($id > 0) {
                return $id;
            }
        }
        return false;
    }

    /**
     * Get a label for the object. Perhaps a Name or Description field.
     * @return string
     */
    public function getLabel()
    {
        if (property_exists($this, '_label_column')) {
            if (property_exists($this, $this->_label_column)) {
                $label_column = $this->_label_column;
                return $this->$label_column;
            }
        }
        if (property_exists($this, 'name')) {
            return $this->name;
        }
        if (property_exists($this, 'description')) {
            return $this->description;
        }
        return "No label for " . get_called_class() . " ID " . $this->getId();
    }

    /**
     * Work out which columns should be saved down.
     */
    public function __calculateSaveDownRows()
    {
        if (!$this->_columns) {
            foreach (get_object_vars($this) as $potential_column => $discard) {
                switch ($potential_column) {
                    case 'table':
                    case substr($potential_column, 0, 1) == "_":
                        // Not a valid column
                        break;
                    default:
                        $this->_columns[] = $potential_column;
                        break;
                }
            }
        }

        // reorder the columns to match get_class_schema
        //TODO: Write test to verify that this works right.
        foreach ($this->getClassSchema() as $schemaKey => $dontCare) {
            if (in_array($schemaKey, $this->_columns)) {
                $sortedColumns[$schemaKey] = $schemaKey;
            }
        }
        foreach ($this->_columns as $column) {
            if (!isset($sortedColumns[$column])) {
                $class_name = get_called_class();
                throw new Exception("No type hinting/docblock found for '{$column}' in '{$class_name}'.", E_USER_WARNING);
            }
        }

        $this->_columns = array_values($sortedColumns);

        // Return sorted columns.
        return $this->_columns;
    }

    /**
     * Load an object from data fed to us as an array (or similar.)
     *
     * @param Array $row
     *
     * @return ActiveRecord
     */
    public function loadFromRow($row)
    {
        // Loop over the columns, sanitise and store it into the new properties of this object.
        foreach ($row as $column => &$value) {
            // Only save columns beginning with a normal letter.
            if (preg_match('/^[a-z]/i', $column)) {
                $this->$column = & $value;
            }
        }
        $this->postConstruct();
        return $this;
    }

    /**
     * Save the selected record.
     * This will do an INSERT or UPDATE as appropriate
     *
     * @param boolean $automatic_reload Whether or not to automatically reload
     *
     * @return ActiveRecord
     */
    public function save($automatic_reload = true)
    {
        // Run Pre-saver.
        $this->preSave();

        // Run Field Fixer.
        $this->__fieldFix();

        // Calculate row to save_down
        $this->__calculateSaveDownRows();
        $primary_key_column = $this->getTablePrimaryKey();

        // Make an array out of the objects columns.
        $data = array();
        foreach ($this->_columns as $column) {
            // Never update the primary key. Bad bad bad. Except if we're versioned.
            if ($column != $primary_key_column || $this instanceof VersionedActiveRecord) {
                $data["`{$column}`"] = $this->$column;
            }
        }

        // If we already have an ID, this is an update.
        $database = DatabaseLayer::getInstance();
        if (!$this->getId() || property_exists($this, '_is_versioned') && $this->_is_versioned == true) {
            $operation = $database->insert($this->getTableName(), $this->getTableAlias());
        } else { // Else, we're an insert.
            $operation = $database->update($this->getTableName(), $this->getTableAlias());
        }

        $operation->setData($data);

        if ($this->getId() && $primary_key_column) {
            $operation->condition($primary_key_column, $this->$primary_key_column);
            $operation->execute();
        } else { // Else, we're an insert.
            $new_id = $operation->execute($this->getClass());
            if ($primary_key_column) {
                $this->$primary_key_column = $new_id;
            }
        }

        // Expire any existing copy of this object.
        SearchIndex::getInstance()->expire($this->getTableName(), $this->getId());

        if ($automatic_reload && $primary_key_column) {
            $this->reload();
        }

        // Run Post Save.
        $this->postSave();

        // Return object. Should this return true/false based on success instead?
        return $this;
    }

    /**
     * Reload the selected record
     * @return ActiveRecord|false
     */
    public function reload()
    {
        $item = $this->getById($this->getId());
        if ($item !== false) {
            $this->loadFromRow($item);
            return $this;
        } else {
            return false;
        }
    }
    /**
     * Delete the selected record
     * @return boolean
     */
    public function delete()
    {
        $database = DatabaseLayer::getInstance();

        $delete = $database->delete($this->getTableName(), $this->getTableAlias());
        $delete->setModel($this);
        $delete->condition($this->getTablePrimaryKey(), $this->getId());
        $delete->execute($this->getClass());

      // Invalidate cache.
        SearchIndex::getInstance()->expire($this->getTableName(), $this->getId());

        return true;
    }

    /**
     * Delete the selected records table.
     * WARNING YO.
     */
    public static function deleteTable()
    {
        $class = get_called_class();
        $object = new $class();
        $table_builder = new TableBuilder($object);
        $table_builder->destroy();
    }

    public static function getTable()
    {
        $class = get_called_class();
        $object = new $class();
        return $object->getTableName();
    }

    /**
     * Set the name of the table to use for this ActiveRecord based object
     * @param $table
     * @return $this
     */
    public function setDatabaseTable($table)
    {
        $this->_table = $table;
        return $this;
    }

    /**
     * Get the name of the table to use for this ActiveRecord based object
     * @return string
     */
    public function getDatabaseTable()
    {
        return $this->_table;
    }

    /**
     * Pull a database record by the slug we're given.
     *
     * @param $slug string Slug
     *
     * @return mixed
     */
    public static function getBySlug($slug)
    {
        $slug_parts = explode("-", $slug, 2);
        $class = get_called_class();
        $temp_this = new $class();
        $primary_key = $temp_this->get_table_primary_key();
        return self::search()->where($primary_key, $slug_parts[0])->execOne();
    }

    /**
     * Get URL slug.
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->getId() . "-" . Util::slugify($this->getLabel());
    }

    public function __toArray($anticipated_rows = null)
    {
        $array = array();
        foreach (get_object_vars($this) as $k => $v) {
            if ($anticipated_rows === null || in_array($k, $anticipated_rows)) {
                $array[$k] = $v;
            }
        }
        return $array;
    }

    public function __toPublicArray()
    {
        $array = array();

        $reflect = new \ReflectionObject($this);
        foreach ($reflect->getProperties(\ReflectionProperty::IS_PUBLIC /* + ReflectionProperty::IS_PROTECTED*/) as $prop) {
            if ($prop->isStatic()) {
                continue;
            }
            $name = $prop->getName();
            $array[$name] = $this->$name;
        }
        return $array;

    }

    public function __toJson($anticipated_rows = null)
    {
        $array = $this->__toArray($anticipated_rows);
        return JsonPrettyPrinter::Json($array);
    }

    public function getClass($without_namespace = false)
    {
        if ($without_namespace) {
            $bits = explode("\\", get_called_class());
            return end($bits);
        } else {
            return get_called_class();
        }
    }

    public function getTableBuilder()
    {
        return new TableBuilder($this);
    }

    /**
     * Fix types of fields to match definition
     */
    public function __fieldFix()
    {
        $schema = $this->getClassSchema();
        foreach ($this->__calculateSaveDownRows() as $column) {
            if (!isset($schema[$column]['type'])) {
                throw new Exception("No type hinting/docblock found for '{$column}' in '" . get_called_class() . "'.", E_USER_WARNING);
            }

            $type = $schema[$column]['type'];
            if ($type == "integer" && !is_int($this->$column)) {
                $this->$column = intval($this->$column);
            }
        }
        return true;
    }

    public function getClassSchema()
    {
        $current = get_class($this);
        $parents[] = $current;
        while ($current = get_parent_class($current)) {
            $parents[] = $current;
        }
        $variables = array();
        $rows = [];
        $abstractRows = [];
        foreach (array_reverse($parents) as $parent) {
            $reflection_class = new \ReflectionClass($parent);
            if (!$reflection_class->isAbstract()) {
                $rows[] = explode("\n", $reflection_class->getDocComment());
            } else {
                $abstractRows[] = explode("\n", $reflection_class->getDocComment());
            }
        }

        foreach ($rows as $rowGroup) {
            foreach ($rowGroup as $row) {
                $property = $this->__parseSchemaDocblockRow($row);
                $variables[][$property['name']] = $property;
            }
        }
        foreach ($abstractRows as $abstractRowGroup) {
            foreach ($abstractRowGroup as $row) {
                $property = $this->__parseSchemaDocblockRow($row);
                $variables[][$property['name']] = $property;
            }
        }
        $merged_variables = call_user_func_array('array_merge', $variables);

        return array_filter($merged_variables);
    }

    private function __parseSchemaDocblockRow($row)
    {
        $row = str_replace("*", "", $row);
        $row = trim($row);
        if (substr($row, 0, 4) == '@var') {
            return $this->__parseClassSchemaProperty($row);
        }
    }

    private function __parseClassSchemaProperty($row)
    {
        $bits = explode(" ", $row);
        $name = trim($bits[1], "$");
        $type = $bits[2];
        $type_bits = explode("(", $type, 2);
        $type = strtolower($type_bits[0]);

        $controls = implode(" ", array_slice($bits, 3));
        $controls = explode(" ", $controls);
        // TODO: Parse controls for relationships and so on.

        if ($type == 'enum' || $type == 'decimal') {
            $options = explode(",", $type_bits[1]);
            foreach ($options as &$option) {
                $option = trim($option);
                $option = trim($option, "'\")");
            }
        } else {
            $length = isset($type_bits[1]) ? trim($type_bits[1], ")") : null;
        }

        $definition = array();
        $definition['name'] = $name;
        $definition['type'] = $type;
        if (isset($length)) {
            $definition['length'] = $length;
        }
        if (isset($options)) {
            $definition['options'] = $options;
        }
        if (in_array("nullable", $controls)) {
            $definition['nullable'] = true;
        } else {
            $definition['nullable'] = false;
        }
        return $definition;
    }
}
