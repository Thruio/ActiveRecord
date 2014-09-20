<?php
namespace Thru\ActiveRecord;

use Thru\ActiveRecord\DatabaseLayer\Exception;

class DatabaseLayer
{

    const DSN_REGEX = '/^(?P<user>\w+)(:(?P<password>\w+))?@(?P<host>[.\w]+)(:(?P<port>\d+))?\\\\(?P<database>\w+)$/im';

    static $instance;
    private $options;

    /**
     * @return DatabaseLayer
     */
    public static function get_instance()
    {
        if (!self::$instance) {
            self::$instance = new DatabaseLayer();
        }
        return self::$instance;
    }

    /**
     * @param array|null $options
     */
    public function __construct($options = null)
    {
        global $active_record_log;
        $active_record_log['ConnectionCount'] = isset($active_record_log['ConnectionCount']) ? $active_record_log['ConnectionCount'] + 1 : 1;
        $this->options = $options;
        if(!isset($this->options['db_dsn'])){
          $this->options['db_dsn'] = $this->_getDsn();
        }
        self::$instance = $this;
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
     * @param $table_name
     * @param null $table_alias
     * @return DatabaseLayer\Update
     */
    public function update($table_name, $table_alias = null)
    {
        return new DatabaseLayer\Update($table_name, $table_alias);
    }
    /**
     * @param $table_name
     * @param null $table_alias
     * @return DatabaseLayer\Update
     */
    public function delete($table_name, $table_alias = null)
    {
        return new DatabaseLayer\Delete($table_name, $table_alias);
    }

    /**
     * @param $table_name
     * @param null $table_alias
     * @return DatabaseLayer\Insert
     */
    public function insert($table_name, $table_alias = null)
    {
      return new DatabaseLayer\Insert($table_name, $table_alias);
    }
    /**
     * @param $table_name
     * @param null $table_alias
     * @return DatabaseLayer\Passthru
     */
    public function passthru($sql = null)
    {
      return new DatabaseLayer\Passthru($sql);
    }

    public function get_table_indexes($table_name){
        $util = new DatabaseLayer\Util();
        return $util->getIndexes($table_name);
    }

    public function get_option($name){
        if(isset($this->options[$name])){
            return $this->options[$name];
        }
        return false;
    }

    private function _getDsn(){
        switch($this->options['db_type']){
            case 'Sqlite':
                $dsn = "sqlite:{$this->options['db_file']}";
                break;
            case 'Mysql':
                $dsn = "mysql:host={$this->options['db_hostname']};port={$this->options['db_port']};dbname={$this->options['db_database']}";
                break;
            default:
                throw new Exception("DB TYPE not supported: {$this->options['db_type']}");
        }
        return $dsn;
    }


    /**
     * Parse a DSN-string, user:password@host:port\database, and break it into it's components.
     * Password is optional.
     *
     * Many thanks to Vision.
     *
     * @param string $dsn DSN string to parse.
     * @return array|bool Array on success, false on error.
     */
    static public function ParseDsn($dsn)
    {
      $result = array
      (
        'user' => '',
        'password' => '',
        'host' => 'localhost',
        'port' => 3306,
        'database' => ''
      );

      if (strlen($dsn) == 0)
      {
        echo "DSN length too short.\n";
        return false;
      }

      if (!preg_match(self::DSN_REGEX, $dsn, $matches))
      {
        echo "DSN regexp match failed\n";
        return false;
      }

      if (count($matches) == 0)
      {
        echo "No matches\n";
        return false;
      }

      foreach ($result as $key => $value)
      {
        if (array_key_exists($key, $matches) and !empty($matches[$key]))
        {
          $result[$key] = $matches[$key];
        }
      }

      return $result;
    }
}