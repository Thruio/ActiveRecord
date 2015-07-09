<?php
namespace Thru\ActiveRecord;

class SearchIndex
{
    private static $instance;

    private $index;

    /**
     * @return SearchIndex
     */
    public static function getInstance()
    {
        if (!self::$instance instanceof SearchIndex) {
            self::$instance = new SearchIndex();
        }
        return self::$instance;
    }

    /**
     * @param $table
     * @param $key
     * @param ActiveRecord $object
     * @return $this
     * @throws DatabaseLayer\ConfigurationException
     */
    public function put($table, $key, ActiveRecord $object)
    {
        if (DatabaseLayer::getInstance()->useCache()) {
            $cache = DatabaseLayer::getInstance()->getCache();
            $cache->save("SearchIndex::{$table}::{$key}", serialize($object));
            return $this;
        } else {
            $this->index[$table][$key] = $object;
            return $this;
        }
    }

    /**
     * @param $table
     * @param $key
     * @return bool
     * @throws DatabaseLayer\ConfigurationException
     */
    public function exists($table, $key)
    {
        if (DatabaseLayer::getInstance()->useCache()) {
            $cache = DatabaseLayer::getInstance()->getCache();
            return $cache->contains("SearchIndex::{$table}::{$key}");
        } else {
            if (isset($this->index[$table][$key])) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * @param $table
     * @param $key
     * @return bool|mixed
     * @throws DatabaseLayer\ConfigurationException
     */
    public function get($table, $key)
    {
        if ($this->exists($table, $key)) {
            if (DatabaseLayer::getInstance()->useCache()) {
                $cache = DatabaseLayer::getInstance()->getCache();
                return unserialize($cache->fetch("SearchIndex::{$table}::{$key}"));
            } else {
                return $this->index[$table][$key];
            }
        } else {
            return false;
        }
    }

    /**
     * @param $table
     * @param $key
     * @return bool
     * @throws DatabaseLayer\ConfigurationException
     */
    public function expire($table, $key)
    {
        if ($this->exists($table, $key)) {
            if (DatabaseLayer::getInstance()->useCache()) {
                $cache = DatabaseLayer::getInstance()->getCache();
                return $cache->delete("SearchIndex::{$table}::{$key}");
            } else {
                unset($this->index[$table][$key]);
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * @return bool
     * @throws DatabaseLayer\ConfigurationException
     */
    public function wipe()
    {
        if (DatabaseLayer::getInstance()->useCache()) {
            $cache = DatabaseLayer::getInstance()->getCache();
            $cache->flushAll();
            return $cache->deleteAll();
        } else {
            $this->index = [];
            return true;
        }
    }
}
