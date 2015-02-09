<?php
/**
 * Created by PhpStorm.
 * User: Baggett
 * Date: 09/02/2015
 * Time: 15:33
 */

use \Thru\ActiveRecord\DatabaseLayer\Select;

class DatabaseLayerSelectTest extends PHPUnit_Framework_TestCase {

  public function setUp(){

  }

  public function tearDown(){

  }

  /**
   * @expectedException         \Thru\ActiveRecord\Exception
   * @expectedExceptionMessage  No table matching alias 'doesntexist' selectable
   */
  public function testBadFieldAliasThrowsException(){
    $select = new Select("test_models");
    $select->fields('doesntexist');
  }

  public function testRange(){
    $select = new Select("test_models");
    $select->range(0,1);
    $result = $select->execute();
    $this->assertTrue(is_array($result));
    $this->assertEquals(1, count($result));
  }
}
