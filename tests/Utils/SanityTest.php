<?php
/**
 * Created by PhpStorm.
 * User: Matthew Baggett
 * Date: 16/02/2015
 * Time: 19:02
 */

namespace tests\Utils;


use Thru\ActiveRecord\Test\TestModel;

class SanityTest extends \PHPUnit_Framework_TestCase {
  public function testOutputEmpty(){
    ob_start();
    TestModel::search()->exec();
    $output = ob_get_contents();
    ob_end_clean();

    $this->assertEmpty($output);
  }
}
