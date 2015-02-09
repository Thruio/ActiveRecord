<?php
/**
 * Created by PhpStorm.
 * User: Baggett
 * Date: 09/02/2015
 * Time: 14:00
 */

class ExceptionTest extends PHPUnit_Framework_TestCase {

  /**
   * @expectedException         \Thru\ActiveRecord\Exception
   * @expectedExceptionMessage  Message Here
   */
  public function testException(){
    throw new \Thru\ActiveRecord\Exception("Message Here");
  }
}
