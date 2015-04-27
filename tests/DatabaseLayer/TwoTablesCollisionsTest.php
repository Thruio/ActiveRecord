<?php
/**
 * Created by PhpStorm.
 * User: Baggett
 * Date: 17/02/2015
 * Time: 16:06
 */

namespace Thru\ActiveRecord\Test;

use Thru\ActiveRecord\DatabaseLayer;

class TwoTablesCollisionsTest extends \PHPUnit_Framework_TestCase {
  /**
   * @expectedException \Thru\ActiveRecord\DatabaseLayer\Exception
   * @expectedExceptionMessage Active Record Cannot insert into more than one table at a time!
   */
  public function testInsertIntoTwoTablesFails(){
    $insert = new DatabaseLayer\Insert("test_models");
    $insert->setTables(array(
      "tm" => new DatabaseLayer\Table("test_models"),
      "tmb" => new DatabaseLayer\Table("test_model_bad")
    ));
    $insert->execute();
  }

  /**
   * @expectedException \Thru\ActiveRecord\DatabaseLayer\Exception
   * @expectedExceptionMessage Active Record Cannot update into more than one table at a time!
   */
  public function testUpdateIntoTwoTablesFails(){
    $update = new DatabaseLayer\Update("test_models");
    $update->setTables(array(
      "tm" => new DatabaseLayer\Table("test_models"),
      "tmb" => new DatabaseLayer\Table("test_model_bad")
    ));
    $update->execute();
  }

  /**
   * @expectedException \Thru\ActiveRecord\DatabaseLayer\Exception
   * @expectedExceptionMessage Active Record Cannot delete from more than one table at a time!
   */
  public function testDeleteFromTwoTablesFails(){
    $delete = new DatabaseLayer\Delete("test_models");
    $delete->setTables(array(
      "tm" => new DatabaseLayer\Table("test_models"),
      "tmb" => new DatabaseLayer\Table("test_model_bad")
    ));
    $delete->execute();
  }
}
