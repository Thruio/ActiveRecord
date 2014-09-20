<?php
namespace Thru\ActiveRecord\DatabaseLayer\Sql;

use Thru\ActiveRecord\DatabaseLayer;
use Thru\ActiveRecord\DatabaseLayer\Exception;
use Thru\ActiveRecord\DatabaseLayer\VirtualQuery;

class Base extends \PDO
{
  protected $query_log;

  private static $_instance;

  public static function factory(){
    $called_class = get_called_class();
    if(!self::$_instance instanceof $called_class){
      self::$_instance = new $called_class();
    }
    return self::$_instance;
  }

  public function __construct(){
    $dsn = DatabaseLayer::get_instance()->get_option('db_dsn');
    $username = null;
    $password = null;
    if(DatabaseLayer::get_instance()->get_option('db_username')){
      $username = DatabaseLayer::get_instance()->get_option('db_username');
    }
    if(DatabaseLayer::get_instance()->get_option('db_password')){
      $password = DatabaseLayer::get_instance()->get_option('db_password');
    }
    parent::__construct($dsn, $username, $password);
  }

  public function query($query, $model = 'StdClass'){
    /* @var $result \PDOStatement */
    $exec_time_start = microtime(true);

    $result = parent::Query($query, \PDO::FETCH_CLASS, $model);

    $error = parent::errorInfo();
    if($error[0] !== '00000'){
      throw new \Exception($error[2]);
    }

    $exec_time_end = microtime(true);
    $exec_time = $exec_time_end - $exec_time_start;
    $this->query_log[] = new Log($query, $exec_time);

    return $result;
  }

  /**
   * Turn a VirtualQuery into a SQL statement
   * @param VirtualQuery $thing
   * @throws Exception
   */
  public function process(VirtualQuery $thing){
    throw new Exception("Base::process should have been overriden");
  }
}
