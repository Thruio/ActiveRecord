<?php

namespace Thru\ActiveRecord\Test;
use Thru\ActiveRecord\DatabaseLayer;

class DatabaseLayerTest extends \PHPUnit_Framework_TestCase {

  public function setUp(){
    DatabaseLayer::destroy_instance();
  }

  public function testConstructMySQL(){
    $database = new DatabaseLayer(array(
      'db_type'     => 'Mysql',
      'db_hostname' => 'localhost',
      'db_port'     => '3306',
      'db_username' => 'travis',
      'db_password' => 'travis',
      'db_database' => 'active_record_test'
    ));
    $this->assertEquals("travis",             $database->get_option("db_username"));
    $this->assertEquals("travis",             $database->get_option("db_password"));
    $this->assertEquals("active_record_test", $database->get_option("db_database"));
    return $database;
  }

  /**
   * @expectedException \Thru\ActiveRecord\DatabaseLayer\ConfigurationException
   * @expectedExceptionMessage DB TYPE not supported: Bogus
   */
  public function testConstructInvalid(){

    $database = new DatabaseLayer(array(
      'db_type'     => 'Bogus',
      'db_hostname' => 'localhost',
      'db_port'     => '3306',
    ));
  }

  /**
   * @depends testConstructMySQL
   */
  public function testConstructBogusSetting(DatabaseLayer $databaseLayer){
    $this->assertFalse($databaseLayer->get_option("not_an_option_really"));
  }

  /**
   * @expectedException \Thru\ActiveRecord\DatabaseLayer\ConfigurationException
   * @expectedExceptionMessage DatabaseLayer has not been configured
   */
  public function testBlankDatabaseLayer(){
    DatabaseLayer::get_instance();
  }


}
