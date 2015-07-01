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
}