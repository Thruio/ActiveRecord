<?php

namespace Thru\ActiveRecord;

use Thru\ActiveRecord\DatabaseLayer\TableBuilder;
use Thru\JsonPrettyPrinter\JsonPrettyPrinter;


class ActiveRecord
{
    static public $MYSQL_FORMAT = "Y-m-d H:i:s";
    protected $_label_column = null;
    protected $_columns;

    /**
     * Start a Search on this type of active record
     * @return Search
     */
    static public function search()
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
      $tableBuilder = $this->get_table_builder();
      $tableBuilder->build($this);
    }

    /**
     * Override-able calls
     */
    public function __post_construct()
    {
    }

    public function __pre_save()
    {
    }

    public function __post_save()
    {
    }

    /*public function __requires_recast()
    {
        return false;
    }*/

    /**
     * Find an item by the Primary Key ID. This does not use the search() functionality
     * @param integer $id
     * @return ActiveRecord
     */
    public function get_by_id($id)
    {
        $database = DatabaseLayer::get_instance();
        $select = $database->select($this->get_table_name(), $this->get_table_alias());
        $select->fields($this->get_table_alias());
        $select->condition($this->get_table_primary_key(), $id);
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
    public function get_table_alias($table_name = null)
    {
        if (!$table_name) {
            $table_name = $this->get_table_name();
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
    public function get_table_name()
    {
        return $this->_table;
    }

    /**
     * Get table primary key column name
     *
     * @return string|false
     */
    public function get_table_primary_key()
    {
        $database = DatabaseLayer::get_instance();
        $keys = $database->get_table_indexes($this->_table);
        if(!isset($keys[0])){
          return false;
        }
        $primary_key = $keys[0]->Column_name;
        return $primary_key;
    }

    /**
     * Get a unique key to use as an index
     *
     * @return string
     */
    public function get_primary_key_index()
    {
        $database = DatabaseLayer::get_instance();

        $keys = $database->get_table_indexes($this->_table);
        $columns = array();
        foreach ($keys as $key) {
            $columns[$key->Column_name] = $key->Column_name;
        }
        $keys = array();
        foreach ($columns as $column) {
            $keys[] = $this->$column;
        }
        return implode("-", $keys);
    }

    /**
     * Get object ID
     * @return integer
     */
    public function get_id()
    {
        $col = $this->get_table_primary_key();
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
    public function get_label()
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
        return "No label for " . get_called_class() . " ID " . $this->get_id();
    }

    /**
     * Work out which columns should be saved down.
     */
    public function _calculate_save_down_rows()
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
        $this->__post_construct();
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
        $this->__pre_save();
        // Calculate row to save_down
        $this->_calculate_save_down_rows();
        $primary_key_column = $this->get_table_primary_key();

        // Make an array out of the objects columns.
        $data = array();
        foreach ($this->_columns as $column) {
            // Never update the primary key. Bad bad bad.
            if ($column != $primary_key_column) {
                $data["`{$column}`"] = $this->$column;
            }
        }

        // If we already have an ID, this is an update.
        $database = DatabaseLayer::get_instance();
        if ($this->get_id()) {
            $operation = $database->update($this->get_table_name(), $this->get_table_alias());
        } else { // Else, we're an insert.
            $operation = $database->insert($this->get_table_name(), $this->get_table_alias());
        }

        $operation->setData($data);

        if ($this->get_id() && $primary_key_column) {
            $operation->condition($primary_key_column, $this->$primary_key_column);
            $operation->execute();
        } else { // Else, we're an insert.
            $new_id = $operation->execute();
            if($primary_key_column) {
                $this->$primary_key_column = $new_id;
            }
        }

        // Expire any existing copy of this object.
        SearchIndex::get_instance()->expire($this->get_table_name(), $this->get_id());

        if ($automatic_reload && $primary_key_column) {
            $this->reload();
        }
        $this->__post_save();
        return $this;
    }

    /**
     * Reload the selected record
     * @return ActiveRecord|false
     */
    public function reload()
    {
      $item = $this->get_by_id($this->get_id());
      if($item !== false){
        $this->loadFromRow($item);
        return $this;
      }else{
        return false;
      }
    }
    /**
     * Delete the selected record
     * @return boolean
     */
    public function delete()
    {
      $database = DatabaseLayer::get_instance();
      $delete = $database->delete($this->get_table_name(), $this->get_table_alias());
      $delete->condition($this->get_table_primary_key(), $this->get_id());
      $delete->execute();

      // Invalidate cache.
      SearchIndex::get_instance()->expire($this->get_table_name(),$this->get_id());

      return true;
    }

    /**
     * Delete the selected records table.
     * WARNING YO.
     */
    public static function delete_table(){
        $class = get_called_class();
        $object = new $class();
        $table_builder = new TableBuilder($object);
        $table_builder->destroy();
    }

    public static function get_table(){
        $class = get_called_class();
        $object = new $class();
        return $object->get_table_name();
    }

    /**
     * Pull a database record by the slug we're given.
     *
     * @param $slug string Slug
     *
     * @return mixed
     */
    static public function get_by_slug($slug)
    {
        $slug_parts = explode("-", $slug, 2);
        $class = get_called_class();
        $temp_this = new $class();
        $primary_key = $temp_this->get_table_primary_key();
        return self::search()->where($primary_key, $slug_parts[0])->execOne();
    }

    /**
     * Recast an object from a parent class to an extending class, if ActiveRecord_class is present
     *
     * @return ActiveRecord
     * @throws exception
     */
    /*public function __recast()
    {
        // If the object has a property called ActiveRecord_class, it can potentially be recast at runtime. There are some dependencies though
        if (property_exists($this, '__active_record_class')) {
            if ($this->__active_record_class !== get_called_class() && $this->__active_record_class != null) {
                if (!class_exists($this->__active_record_class)) {
                    throw new Exception("Active Record Class: {$this->__active_record_class} does not exist.");
                }
                if (!is_subclass_of($this->__active_record_class, get_called_class())) {
                    throw new Exception("Active Record Class: " . $this->__active_record_class . " must extend " . get_called_class());
                }
                $recast_class = $this->__active_record_class;
                $new_this = new $recast_class();
                $new_this->loadFromRow((array)$this);
                return $new_this;
            }
        }
        return $this;
    }*/

    /**
     * Get URL slug.
     *
     * @return string
     */
    public function get_slug()
    {
        return $this->get_id() . "-" . Util::slugify($this->get_label());
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

    public function __toPublicArray(){
        $array = array();

        $reflect = new \ReflectionObject($this);
        foreach ($reflect->getProperties(\ReflectionProperty::IS_PUBLIC /* + ReflectionProperty::IS_PROTECTED*/) as $prop) {
            $name = $prop->getName();
            $array[$name] = $this->$name;
        }
        return $array;

    }

    public function __toJson($anticipated_rows = null){
        $array = $this->__toArray($anticipated_rows);
        return JsonPrettyPrinter::Json($array);
    }

    public function get_class($without_namespace = false){
        if($without_namespace){
          $bits = explode("\\", get_called_class());
          return end($bits);
        }else{
          return get_called_class();
        }
    }

    public function get_table_builder(){
        return new TableBuilder($this);
    }

    /**
     * Fix types of fields to match definition
     */
    public function field_fix(){
        $schema = $this->get_class_schema();

        foreach($this->_calculate_save_down_rows() as $column){
            $type = $schema[$column]['type'];
            if($type == "integer" && !is_int($this->$column)){
                $this->$column = intval($this->$column);
            }
        }

        return true;
    }

    public function get_class_schema(){
        $reflection_class = new \ReflectionClass($this);
        $rows = explode("\n", $reflection_class->getDocComment());
        $variables = array();
        foreach($rows as &$row){
            $row = str_replace("*", "", $row);
            $row = trim($row);
            if(substr($row,0,4) == '@var'){
                $property = $this->_parse_class_schema_property($row);
                $variables[$property['name']] = $property;
            }
        }
        return $variables;
    }

    private function _parse_class_schema_property($row){
        $bits = explode(" ", $row);
        $name = trim($bits[1],"$");
        $type = $bits[2];
        $type_bits = explode("(", $type, 2);
        $type = strtolower($type_bits[0]);

        $controls = array_slice($bits,3);
        // TODO: Parse controls for relationships and so on.

        if($type == 'enum' || $type == 'decimal'){
            $options = explode(",", $type_bits[1]);
            foreach($options as &$option){
                $option = trim($option);
                $option = trim($option, "'\")");
            }
        }else{
            $length = isset($type_bits[1]) ? trim($type_bits[1],")") : null;
        }

        $definition = array();
        $definition['name'] = $name;
        $definition['type'] = $type;
        if(isset($length)) {
            $definition['length'] = $length;
        }
        if(isset($options)){
            $definition['options'] = $options;
        }
        return $definition;

    }
}