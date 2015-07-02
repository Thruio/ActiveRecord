<?php

namespace Thru\ActiveRecord\Test;

use Thru\ActiveRecord\DatabaseLayer;
use \Thru\ActiveRecord\DatabaseLayer\Select;
use Thru\ActiveRecord\Test\Models\TestModel;

class DatabaseLayerSelectTest extends \PHPUnit_Framework_TestCase {

  /**
   * @expectedException         \Thru\ActiveRecord\Exception
   * @expectedExceptionMessage  No table matching alias 'doesntexist' selectable
   */
  public function testBadFieldAliasThrowsException(){
    $select = new Select("test_models");
    $select->fields('doesntexist');
  }

  public function testRange(){
    $test_model = new TestModel();
    $test_model->integer_field = 1;
    $test_model->text_field = "feh";
    $test_model->date_field = date("Y-m-d H:i:s");
    $test_model->save();

    $select = new Select("test_models");
    $select->range(0,1);
    $result = $select->execute();
    $this->assertTrue(is_array($result));
    $this->assertEquals(1, count($result));

    $test_model->delete();
  }
}
