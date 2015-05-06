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
      if(DatabaseLayer::get_instance()->get_option('db_debug')){
        $file_path = DatabaseLayer::get_instance()->get_option('db_log');
        global $test_id;
        if(isset($test_id)) {
          $file_path = str_replace("%test%", $test_id, $file_path);
        }
        file_put_contents($file_path, str_replace("\n", " ", $query) . "\n", FILE_APPEND);
      }
      $exec_time_end = microtime(true);
      $exec_time = $exec_time_end - $exec_time_start;
      $this->query_log[] = new Log($query, $exec_time);
      return $result;
    }catch(\PDOException $e){
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
          throw new DatabaseLayer\Exception(parent::errorCode() . ": " . $error[2]);
      }
    }
  }

  /**
   * Turn a VirtualQuery into a SQL statement
   * @param VirtualQuery $thing
   * @return DatabaseLayer\Response
   * @throws Exception
   */
  public function process(VirtualQuery $thing){
    throw new Exception("Base::process should have been overridden");
  }
}
