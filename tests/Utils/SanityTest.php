<?php
namespace Thru\ActiveRecord\Test;

use Thru\ActiveRecord\DatabaseLayer;
use Thru\ActiveRecord\Test\Models\TestModel;

class SanityTest extends \PHPUnit_Framework_TestCase
{
    public function testOutputEmpty()
    {
        ob_start();
        TestModel::search()->exec();
        $output = ob_get_contents();
        ob_end_clean();
        if (!empty($output)) {
            $this->markTestSkipped("Output not clean");
        } else {
            $this->assertEmpty($output);
        }
    }

    public function testWorksWithMonolog()
    {
        TestModel::search()->exec();
    }

    public function testWorksWithoutMonolog()
    {
        $logger = DatabaseLayer::get_instance()->getLogger();

        DatabaseLayer::get_instance()->setLogger(null);
        TestModel::search()->exec();

        DatabaseLayer::get_instance()->setLogger($logger);
    }
}
