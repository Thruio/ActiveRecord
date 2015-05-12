<?php
namespace Thru\ActiveRecord\Test;

use Thru\ActiveRecord\Test\Models\TestModel;

class SanityTest extends \PHPUnit_Framework_TestCase
{
    public function testOutputEmpty()
    {
        ob_start();
        TestModel::search()->exec();
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEmpty($output);
    }
}
