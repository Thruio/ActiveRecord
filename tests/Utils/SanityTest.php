<?php
namespace Thru\ActiveRecord\Test;

use Thru\ActiveRecord\Test\Models\TestModel;

class SanityTest extends \PHPUnit_Framework_TestCase {
  public function testOutputEmpty(){
    ob_start();
    TestModel::search()->exec();
    $output = ob_get_contents();
    ob_end_clean();

    if(!empty($output)){
        echo "Output: \n";
        var_dump($output);
    }
    $this->assertEmpty($output);
  }
}
