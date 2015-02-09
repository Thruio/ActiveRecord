<?php

namespace Thru\ActiveRecord;

use Thru\ActiveRecord\ActiveRecord;

class VersionedActiveRecord extends ActiveRecord
{
    public $version;
    public $created_date;
    public $created_uid;
    private $_created_user_object;

    /**
     * Get the user that created this item.
     *
     * @return UserActiveRecord|false
     */
    public function get_created_user()
    {
        if (!$this->_created_user_object instanceof UserActiveRecord) {
            $this->_created_user_object = UserActiveRecord::search()->where('uid', $this->created_uid)->execOne();
        }
        return $this->_created_user_object;
    }

    /**
     * Test to see if this object uses logical deletion
     *
     * @return boolean
     */
    public function use_logical_deletion()
    {
        if (isset($this->_cfg_deleteable)) {
            if ($this->_cfg_deleteable == TRUE) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * Delete the object.
     * If we're using logical deletion, mark it deleted and save it, otherwise, delete as usual
     *
     * @see ActiveRecord::delete()
     */
    public function delete()
    {
        if ($this->use_logical_deletion()) {

            db_update($this->get_table_name())
                ->fields(array(
                    'deleted' => 'Yes'
                ))
                ->condition($this->get_table_primary_key(), $this->get_id())
                ->execute();
            return TRUE;
        } else {
            return parent::delete();
        }
    }


    /**
     * Test to see if this object uses weighting
     *
     * @return boolean
     */
    public function use_weighting()
    {
        if (isset($this->_cfg_orderable)) {
            if ($this->_cfg_orderable == TRUE) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * Find out what the heaviest object is in terms of weighting
     * @return integer weight
     */
    private function _weighting_get_heaviest()
    {
        $heaviest_object = db_select($this->get_table_name(), $this->get_table_alias())
            ->orderBy('weight', 'DESC')
            ->fields($this->get_table_alias(), array('weight'))
            ->range(0, 1)
            ->execute()
            ->fetchObject();
        return $heaviest_object->weight;
    }

    /**
     * Save the object down.
     * VersionedActiveRecord uses versioning, so this entirely surplants the standard save() function
     *
     * @see ActiveRecord::save()
     * @return ActiveRecord;
     */
    public function save($automatic_reload = true)
    {
        if (isset($this->version)) {
            $this->version = $this->version + 1;
        } else {
            $this->version = 1;
        }
        $this->created_date = date(self::$MYSQL_FORMAT);
        $this->created_uid = $GLOBALS['user']->uid;

        // Calculate row to save_down
        $this->_calculate_save_down_rows();

        $primary_key_column = $this->get_table_primary_key();

        // Make an array out of the objects columns.
        $data = array();
        foreach ($this->_columns as $column) {
            $data["`{$column}`"] = $this->$column;
        }

        // Insert new version
        $insert_sql = db_insert($this->_table);
        $insert_sql->fields($data);
        $new_id = $insert_sql->execute();
        $this->$primary_key_column = $new_id;
        if ($automatic_reload) {
            $this->reload();
        }

        return $this;
    }

    /**
     * Override the __post_construct so we can check & cleanup weighting, if its in use
     * @see ActiveRecord::__post_construct()
     */
    public function __post_construct()
    {
        if ($this->use_weighting()) {
            if ($this->weight == -1) {
                $this->weight = $this->_weighting_get_heaviest() + 1;
                $this->save();
            }
        }
    }

    public function get_version_history_link()
    {
        return "version_history/" . get_called_class() . "/id/" . $this->get_id() . "/v/" . $this->version;
    }

    /**
     * Get a unique key to use as an index.
     * Since this is
     *
     * @return string
     */
    public function get_primary_key_index()
    {
        $keys_search = db_query("SHOW INDEX FROM {$this->_table} WHERE Key_name = 'PRIMARY'");
        $keys = $keys_search->fetchAll();
        $columns = array();
        foreach ($keys as $key) {
            $columns[$key->Column_name] = $key->Column_name;
        }
        // Since this object is versioned, remove the version column from index.
        unset($columns['version']);
        $keys = array();
        foreach ($columns as $column) {
            $keys[] = $this->$column;
        }
        return implode("-", $keys);;
    }
}