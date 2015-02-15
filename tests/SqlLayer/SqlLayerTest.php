<?php
/**
 * Created by PhpStorm.
 * User: geusebio
 * Date: 09/02/15
 * Time: 22:00
 */

use \Thru\ActiveRecord\DatabaseLayer\VirtualQuery;

class SqlLayerMysqlTest extends PHPUnit_Framework_TestCase {

  private $base;
  private $mysql;

  public function setUp(){
    $this->base = new \Thru\ActiveRecord\DatabaseLayer\Sql\Base();
    $this->mysql = new \Thru\ActiveRecord\DatabaseLayer\Sql\Mysql();
    if(!class_exists("Bogus")) {
      eval("class Bogus extends \\Thru\\ActiveRecord\\DatabaseLayer\\VirtualQuery{}");
    }
  }

  /**
   * @expectedException         \Thru\ActiveRecord\Exception
   * @expectedExceptionMessage  Operation Bogus not supported
   */
  public function testProcessMySqlExceptionOnInvalidOperation(){
    $bogus_virtual_query = new Bogus();
    $this->mysql->process($bogus_virtual_query);
  }

  /**
   * @expectedException         \Thru\ActiveRecord\Exception
   * @expectedExceptionMessage  Base::process should have been overriden
   */
  public function testProcessBaseExceptionOnInvalidOperation(){
    $bogus_virtual_query = new Bogus();
    $this->base->process($bogus_virtual_query);
  }

}
