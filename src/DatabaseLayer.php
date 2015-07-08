<?php
namespace Thru\ActiveRecord;

use Monolog\Logger;
use Thru\ActiveRecord\DatabaseLayer\ConfigurationException;

class DatabaseLayer
{

    const DSN_REGEX = '/^(?P<user>\w+)(:(?P<password>\w+))?@(?P<host>[.\w]+)(:(?P<port>\d+))?\\\\(?P<database>\w+)$/im';

    private static $instance;
    private $options;
    private $logger;

    /**
     * @throws ConfigurationException
     * @return DatabaseLayer
     */
    public static function getInstance()
    {
        if (!DatabaseLayer::$instance) {
            throw new ConfigurationException("DatabaseLayer has not been configured");
        }
        return DatabaseLayer::$instance;
    }

    public static function setInstance(DatabaseLayer $instance)
    {
        self::$instance = $instance;
    }

    public function setLogger(Logger $logger = null)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @returns Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    public static function destroyInstance()
    {
        self::$instance = null;
        return true;
    }

    /**
     * @param array|null $options
     */
    public function __construct($options = null)
    {
        $this->options = $options;
        if (!isset($this->options['db_dsn'])) {
            $this->options['db_dsn'] = $this->__getDsn();
        }
        self::$instance = $this;
    }

    /**
     * @param $table_name
     * @return DatabaseLayer\LockController
     */
    public function lockController($table_name, $table_alias = null)
    {
        return new DatabaseLayer\LockController($table_name, $table_alias);
    }

    /**
     * @param $table_name
     * @param null $table_alias
     * @return DatabaseLayer\Select
     */
    public function select($table_name, $table_alias = null)
    {
        return new DatabaseLayer\Select($table_name, $table_alias);
    }

    /**
     * @param string $table_name
     * @param string $table_alias
     * @return DatabaseLayer\Update
     */
    public function update($table_name, $table_alias = null)
    {
        return new DatabaseLayer\Update($table_name, $table_alias);
    }
    /**
     * @param string $table_name
     * @param string $table_alias
     * @return DatabaseLayer\Delete
     */
    public function delete($table_name, $table_alias = null)
    {
        return new DatabaseLayer\Delete($table_name, $table_alias);
    }

    /**
     * @param string $table_name
     * @param string $table_alias
     * @return DatabaseLayer\Insert
     */
    public function insert($table_name, $table_alias = null)
    {
        return new DatabaseLayer\Insert($table_name, $table_alias);
    }

    /**
     * @param $sql
     * @return DatabaseLayer\Passthru
     */
    public function passthru($sql = null)
    {
        return new DatabaseLayer\Passthru($sql);
    }

    public function getTableIndexes($table_name)
    {
        $util = new DatabaseLayer\Util();
          $indexes = $util->getIndexes($table_name);
          return $indexes;
    }

    /**
     * @param string $name
     *
     * @return string|null
     */
    public function getOption($name)
    {
        if (isset($this->options[$name])) {
            return $this->options[$name];
        }
        return false;
    }

    /**
     * @return string|false
     * @throws ConfigurationException
     */
    private function __getDsn()
    {
        switch ($this->options['db_type']) {
            case 'Mysql':
                $dsn = "mysql:host={$this->options['db_hostname']};port={$this->options['db_port']};dbname={$this->options['db_database']}";
                break;
            case 'Sqlite':
                $dsn = "sqlite:{$this->options['db_file']}";
                break;
            default:
                throw new ConfigurationException("DB TYPE not supported: {$this->options['db_type']}");
        }
        return $dsn;
    }
}
