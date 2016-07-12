<?php

namespace Thru\ActiveRecord\Test;

use Thru\ActiveRecord\DatabaseLayer;
use Thru\ActiveRecord\Test\Models\TestModel;

class CachingTest extends \PHPUnit_Framework_TestCase
{
    public function testCachingDelete()
    {
        $test = new TestModel();
        $test->integer_field = 2;
        $test->text_field = "test text";
        $test->date_field = date("Y-m-d H:i:s");
        $test->save();

        $testDeleted = TestModel::search()->where("test_model_id",$test->test_model_id)->execOne();

        $this->assertInstanceOf(TestModel::class,$testDeleted);

        $test->delete();

        $testDeleted = TestModel::search()->where("test_model_id",$test->test_model_id)->execOne();

        $this->assertFalse($testDeleted);
    }
}