<?php
namespace Thru\ActiveRecord\Test;

use \Thru\ActiveRecord\Test\Models\TestModelWithNameLabel;
use \Thru\ActiveRecord\Test\Models\TestModelSortable;

class SearchRecordsTest extends \PHPUnit_Framework_TestCase
{

    /* @var $one TestModelSortable */
    private $one;
    /* @var $two TestModelSortable */
    private $two;
    /* @var $three TestModelSortable */
    private $three;

    public function setUp()
    {
        TestModelSortable::deleteTable();
        $this->one = new TestModelSortable();
        $this->two = new TestModelSortable();
        $this->three = new TestModelSortable();

        $this->one->integer_field = 3;
        $this->two->integer_field = 2;
        $this->three->integer_field = 1;

        $this->one->text_field = "Dog";
        $this->two->text_field = "Parakeet";
        $this->three->text_field = "Cat";

        $this->one->date_field = "1990-06-01 04:00:00";
        $this->two->date_field = "1983-01-08 00:00:00";
        $this->three->date_field = date("Y-m-d H:i:s", strtotime("tomorrow"));

        $this->one->save();
        $this->two->save();
        $this->three->save();
    }

    public function tearDown()
    {
        //TestModelSortable::delete_table();
    }

    public function testSearchBySlug()
    {
        $slug_original = new TestModelWithNameLabel();
        $slug_original->name = "Bleh";
        $slug_original->something_else = "Example";
        $slug_original->save();

        $this->assertStringMatchesFormat("%d-example", $slug_original->getSlug());

        $slug_searched = TestModelWithNameLabel::getBySlug($slug_original->getSlug());

        $this->assertNotFalse($slug_searched);

        $this->assertEquals($slug_original->test_model_with_label_id, $slug_searched->test_model_with_label_id);
    }

    public function testSearchOrder()
    {
        $sort_by_num_desc = TestModelSortable::search()->order("integer_field", "DESC")->exec();
        $sort_by_num_asc = TestModelSortable::search()->order("integer_field", "ASC")->exec();

        $sort_by_text_desc = TestModelSortable::search()->order("text_field", "DESC")->exec();
        $sort_by_text_asc = TestModelSortable::search()->order("text_field", "ASC")->exec();

        $sort_by_date_desc = TestModelSortable::search()->order("date_field", "DESC")->exec();
        $sort_by_date_asc = TestModelSortable::search()->order("date_field", "ASC")->exec();

        $this->assertEquals(1, $this->one->test_model_id);
        $this->assertEquals(2, $this->two->test_model_id);
        $this->assertEquals(3, $this->three->test_model_id);

        $this->assertEquals(array(1,2,3), array_keys($sort_by_num_desc));
        $this->assertEquals(array(3,2,1), array_keys($sort_by_num_asc));
        $this->assertEquals(array(2,1,3), array_keys($sort_by_text_desc));
        $this->assertEquals(array(3,1,2), array_keys($sort_by_text_asc));
        $this->assertEquals(array(3,1,2), array_keys($sort_by_date_desc));
        $this->assertEquals(array(2,1,3), array_keys($sort_by_date_asc));
    }

    public function testSearchIn()
    {
        $in_result = TestModelSortable::search()
        ->where('test_model_id', array(1,3), "IN")
        ->order("test_model_id", "ASC")
        ->exec();

        $in_result = array_values($in_result);
        $this->assertEquals(2, count($in_result));
        $this->assertEquals(1, $in_result[0]->test_model_id);
        $this->assertEquals(3, $in_result[1]->test_model_id);
    }

    public function testSearchNotIn()
    {
        $in_result = TestModelSortable::search()
        ->where('test_model_id', array(1,3), "NOT IN")
        ->order("test_model_id", "ASC")
        ->exec();

        $in_result = array_values($in_result);
        $this->assertEquals(1, count($in_result));
        $this->assertEquals(2, $in_result[0]->test_model_id);
    }

    public function testSearchRand()
    {
        $random_result = TestModelSortable::search()
        ->order("test_model_id", "rand")
        ->limit(1, 0)  //Not strictly necessary but helps coverage
        ->execOne();
        $this->assertTrue($random_result instanceof TestModelSortable);
    }

    public function testSearchDirectCondition()
    {
        $condition = new \Thru\ActiveRecord\SearchCondition("text_field", "dog", "LIKE");
        $result = TestModelSortable::search()->condition($condition)->execOne();

        $this->assertTrue($result instanceof TestModelSortable);
        $this->assertEquals("Dog", $result->text_field);
    }

    public function testSearchWithoutOffset()
    {
        $array = TestModelSortable::search()->order("test_model_id", "ASC")->limit(1)->exec();
        $this->assertEquals("Dog", end($array)->text_field);
    }

    public function testSearchWithOffset()
    {
        $array = TestModelSortable::search()->order("test_model_id", "ASC")->limit(1, 1)->exec();
        $this->assertEquals("Parakeet", end($array)->text_field);
    }

    /**
   * @expectedException \Thru\ActiveRecord\DatabaseLayer\Exception
   * @expectedExceptionMessage Bad ORDER direction: invalid
   */
    public function testSearchWithInvalidOrder()
    {
        TestModelSortable::search()->order("test_model_id", "invalid")->exec();
    }

    public function testVirtualQueryTablesSane()
    {
        $insert = new \Thru\ActiveRecord\DatabaseLayer\Insert("test_models");
        $tables = $insert->getTables();
        $this->assertArrayHasKey("tm", $tables);
        $this->assertEquals("Thru\\ActiveRecord\\DatabaseLayer\\Table", get_class($tables['tm']));
        $this->assertEquals("test_models", $tables['tm']->getName());
        $this->assertEquals("tm", $tables['tm']->getAlias());
    }
}
