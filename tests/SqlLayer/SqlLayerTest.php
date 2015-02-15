<?php
/**
 * Created by PhpStorm.
 * User: geusebio
 * Date: 09/02/15
 * Time: 22:00
 */

use \Thru\ActiveRecord\Test\TestModel;
use \Thru\ActiveRecord\Test\TestModelExtendedTypes;
use \Thru\ActiveRecord\Test\TestModelWithNameLabel;
use \Thru\ActiveRecord\Test\TestModelSortable;
use \Thru\ActiveRecord\Test\TestModelSearchOnly;
use \Thru\ActiveRecord\DatabaseLayer\VirtualQuery;
use \Thru\ActiveRecord\DatabaseLayer\IndexException;

class SqlLayerMysqlTest extends PHPUnit_Framework_TestCase {

  /**
   * @var $base \Thru\ActiveRecord\DatabaseLayer\Sql\Base
   * @var $mysql \Thru\ActiveRecord\DatabaseLayer\Sql\Mysql
   */
  private $base;
  private $mysql;

  public function setUp(){
    $this->base = new \Thru\ActiveRecord\DatabaseLayer\Sql\Base();
    $this->mysql = new \Thru\ActiveRecord\DatabaseLayer\Sql\Mysql();
    if(!class_exists("Bogus")) {
      eval("class Bogus extends \\Thru\\ActiveRecord\\DatabaseLayer\\VirtualQuery{}");
    }

    TestModel::delete_table();
    TestModelExtendedTypes::delete_table();
    TestModelWithNameLabel::delete_table();
    TestModelSortable::delete_table();
    TestModelSearchOnly::delete_table();
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

  public function testIndex(){
    $model = new TestModelSortable();
    $model->integer_field = 1;
    $model->text_field = "Feh";
    $model->date_field = date("Y-m-d H:i:s");
    $model->save();

    $mysql = new \Thru\ActiveRecord\DatabaseLayer\Sql\Mysql();
    $indexes = $mysql->getIndexes("test_models_sortable");
    $this->assertTrue(is_array($indexes));
    $this->assertTrue($indexes[0]->Column_name == "test_model_id");
  }

  /**
   * @expectedException \Thru\ActiveRecord\DatabaseLayer\IndexException
   */
  public function testIndexFailOnNonExistant(){
    $mysql = new \Thru\ActiveRecord\DatabaseLayer\Sql\Mysql();
    $mysql->getIndexes("test_models_sortable");
  }

}
