<?php
namespace Thru\ActiveRecord\Test;

class ExceptionTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @expectedException         \Thru\ActiveRecord\Exception
     * @expectedExceptionMessage  Message Here
     */
    public function testException()
    {
        throw new \Thru\ActiveRecord\Exception("Message Here");
    }
}
