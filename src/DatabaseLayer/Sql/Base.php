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
    $username = !empty($username)?$username:null;
    $password = !empty($password)?$password:null;

    $options = array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION);

    parent::__construct($dsn, $username, $password, $options);
  }

  public function query($query, $model = 'StdClass'){
    /* @var $result \PDOStatement */
    try {
      $exec_time_start = microtime(true);
      $result = parent::Query($query, \PDO::FETCH_CLASS, $model);
      #echo " *** " . parent::errorCode() . " ({$model}) " . str_replace("\n", " ", $query) . "\n";
      $exec_time_end = microtime(true);
      $exec_time = $exec_time_end - $exec_time_start;
      if(DatabaseLayer::get_instance()->getLogger()) {
        DatabaseLayer::get_instance()->getLogger()->addDebug($query);
      }

      $this->query_log[] = new Log($query, $exec_time);
      return $result;
    }catch(\PDOException $e){
      $this->handleError($model, $query);
    }
  }

  public function handleError($model, $query){
    $error = parent::errorInfo();

    switch(parent::errorCode()){
      case '42S02':
        if($model != 'StdClass'){
          $instance = new $model();
          if($instance instanceof ActiveRecord) {
            $table_builder = new TableBuilder($instance);
            $table_builder->build();
            return $this->query($query); // Re-run the query
          }
          throw new DatabaseLayer\TableDoesntExistException(parent::errorCode() . ": " . $error[2]);
        }
        break;
      default:
        // Write exception to log.
        if(DatabaseLayer::get_instance()->getLogger()) {
          DatabaseLayer::get_instance()->getLogger()->addError("Active Record Exception in " . $model . "\n\n" . parent::errorCode() . ": " . $error[2] . "\n\nrunning:\n\n{$query}");
        }
        throw new DatabaseLayer\Exception(parent::errorCode() . ": " . $error[2] . ".\n\n" . $query);
    }
  }

  /**
   * Turn a VirtualQuery into a SQL statement
   * @param VirtualQuery $thing
   * @throws Exception
   */
  public function process(VirtualQuery $thing){
    throw new Exception("Base::process should have been overridden");
  }
}
