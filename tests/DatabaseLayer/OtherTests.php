<?php

namespace Thru\ActiveRecord\Test;

use Thru\ActiveRecord\DatabaseLayer;
use Thru\ActiveRecord\Test\Models\NotStdClass;

class OtherTests extends \PHPUnit_Framework_TestCase
{
  /**
   * @expectedException \Thru\ActiveRecord\DatabaseLayer\TableDoesntExistException
   * @expectedExceptionMessage 42S02: Table 'active_record_test.bogus' doesn't exist
   */
  public function testDeleteNonStdClass()
  {
    $database = DatabaseLayer::get_instance();

    $notStdClass = new NotStdClass();

    $delete = $database->delete("bogus");
    $delete->setModel($notStdClass);
    $delete->execute();
  }

  /**
   * @expectedException \Thru\ActiveRecord\DatabaseLayer\Exception
   * @expectedExceptionMessage Query made PDO sad:
   */
  public function testTableExistsFailure(){
    //$this->markTestSkipped("Disabled until further notice. This exposed an actual PHP bug. See: https://bugs.php.net/bug.php?id=69063");
    $database = DatabaseLayer::get_instance();
    $select = $database->select("table_doesnt_exist");
    $select->execute("NotStdClass");
  }
}