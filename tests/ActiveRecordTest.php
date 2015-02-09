<?php
/**
 * Created by PhpStorm.
 * User: Baggett
 * Date: 09/02/2015
 * Time: 15:33
 */

use \Thru\ActiveRecord\Test\TestModel;
class ActiveRecordTest extends PHPUnit_Framework_TestCase {
  public function testActiveRecordConstruct(){
    $test_model = new TestModel();
    $this->assertEquals("TestModel", get_class($test_model));
  }
}
