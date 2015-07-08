<?php
namespace Thru\ActiveRecord\DatabaseLayer\Sql;

use Thru\ActiveRecord\ActiveRecord;
use Thru\ActiveRecord\DatabaseLayer;
use Thru\ActiveRecord\DatabaseLayer\VirtualQuery;
use Thru\ActiveRecord\DatabaseLayer\TableBuilder;
use Thru\ActiveRecord\Exception;

class Base extends \PDO
{
    protected $query_log;

    private static $_instance;

    public static function factory()
    {
        $called_class = get_called_class();
        if (!self::$_instance instanceof $called_class) {
            self::$_instance = new $called_class();
        }
        return self::$_instance;
    }

    public function __construct()
    {
        $dsn = DatabaseLayer::getInstance()->getOption('db_dsn');
        $username = null;
        $password = null;
        if (DatabaseLayer::getInstance()->getOption('db_username')) {
            $username = DatabaseLayer::getInstance()->getOption('db_username');
        }
        if (DatabaseLayer::getInstance()->getOption('db_password')) {
            $password = DatabaseLayer::getInstance()->getOption('db_password');
        }
        $username = !empty($username)?$username:null;
        $password = !empty($password)?$password:null;

      //$options = array(self::ATTR_ERRMODE => self::ERRMODE_EXCEPTION);

        parent::__construct($dsn, $username, $password); //, $options);

        $this->setAttribute(self::ATTR_ERRMODE, self::ERRMODE_EXCEPTION);
    }

    public function query($query, $model = 'StdClass')
    {
      /* @var $result \PDOStatement */
      #echo "*** Model in Query: " . $model . "\n";
        try {
            $exec_time_start = microtime(true);
            $result = parent::Query($query, \PDO::FETCH_CLASS, $model);

            $exec_time_end = microtime(true);
            $exec_time = $exec_time_end - $exec_time_start;
            if (DatabaseLayer::getInstance()->getLogger()) {
                DatabaseLayer::getInstance()->getLogger()->addDebug("{$exec_time} sec: {$query}");
            }

            $this->query_log[] = new Log($query, $exec_time);
            return $result;
        } catch (\PDOException $e) {
            if (DatabaseLayer::getInstance()->getLogger()) {
                DatabaseLayer::getInstance()->getLogger()->addDebug("Query() Caught Exception: {$e->getMessage()}");
            }
            $this->handleError($model, $query, $e);
        }
    }

    public function handleError($model, $query, \PDOException $e)
    {
      #echo "*** Model in handleError: " . $model . "\n";
        switch ($e->getCode()) {
          // MySQL table missing
            case '42S02':
            // SQLite table missing
            case 'HY000' && (stripos($e->getMessage(), "no such table") !== false):
                if ($model != 'StdClass') {
                    $instance = new $model();
                    if ($instance instanceof ActiveRecord) {
                        $table_builder = new TableBuilder($instance);
                        $table_builder->build();
                        return $this->query($query, $model); // Re-run the query
                    }
                }
                throw new DatabaseLayer\TableDoesntExistException($e->getCode() . ": " . $e->getMessage());
            default:
              // Write exception to log.
                if (DatabaseLayer::getInstance()->getLogger()) {
                    DatabaseLayer::getInstance()->getLogger()->addError("Active Record Exception in " . $model . "\n\n" . $e->getCode() . ": " . $e->getMessage() . "\n\nrunning:\n\n{$query}");
                }
                throw new DatabaseLayer\Exception($e->getCode() . ": " . $e->getMessage() . ".\n\n" . $query);
        }
    }

  /**
   * Turn a VirtualQuery into a SQL statement
   * @param VirtualQuery $thing
   * @throws Exception
   */
    public function process(VirtualQuery $thing)
    {
        throw new Exception("Base::process should have been overridden");
    }
}
