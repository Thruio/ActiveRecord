<?php
namespace Thru\ActiveRecord\Test;

class UtilTest extends \PHPUnit_Framework_TestCase
{
    public function testUtilSlugify()
    {
        $sluggified = \Thru\ActiveRecord\Util::slugify("Some terrible sentence with a ú and some numbers 2342343 and then a bonus umläut.");
        $this->assertEquals("some-terrible-sentence-with-a-u-and-some-numbers-2342343-and-then-a-bonus-umlaut", $sluggified);

        $this->assertEquals('n-a', \Thru\ActiveRecord\Util::slugify(''));
    }
}
