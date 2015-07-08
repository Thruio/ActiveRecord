<?php
namespace Thru\ActiveRecord\Test;

use Thru\ActiveRecord\DatabaseLayer;
use Thru\ActiveRecord\DatabaseLayer\VirtualQuery;
use \Thru\ActiveRecord\Test\Models\TestModel;
use \Thru\ActiveRecord\Test\Models\TestModelExtendedTypes;
use Thru\ActiveRecord\Test\Models\TestModelNullable;
use \Thru\ActiveRecord\Test\Models\TestModelWithNameLabel;
use \Thru\ActiveRecord\Test\Models\TestModelSortable;
use \Thru\ActiveRecord\Test\Models\TestModelSearchOnly;
use \Thru\ActiveRecord\Test\Models\TestModelNoKey;
use \Thru\ActiveRecord\Test\Models\TestModelBad;
use \Thru\JsonPrettyPrinter\JsonPrettyPrinter;
use \Faker;

class ActiveRecordTest extends BaseTest
{

    public function tearDown()
    {
        TestModel::delete_table();
        TestModelExtendedTypes::delete_table();
        TestModelWithNameLabel::delete_table();
        TestModelSortable::delete_table();
        TestModelSearchOnly::delete_table();
        TestModelNoKey::delete_table();
    }

    public function testTableName()
    {
        $this->assertEquals("test_models", TestModel::get_table());
    }

    public function testConstruct()
    {
        $test_model = new TestModel();
        $this->assertEquals("Thru\\ActiveRecord\\Test\\Models\\TestModel", get_class($test_model));
        $this->assertTrue(in_array("Thru\\ActiveRecord\\ActiveRecord", class_parents($test_model)));
    }

    public function testSearchEmptyResult()
    {
        $this->assertEquals(0, TestModel::search()->count());
    }

    public function testCreate()
    {
      /* @var $test_model TestModel */
      /* @var $result_object TestModel */
        $test_model = TestModel::factory();
        $test_model->integer_field = $this->faker->numberBetween(0, 9999999);
        $test_model->text_field = $this->faker->paragraph(5);
        $test_model->date_field = $this->faker->date("Y-m-d H:i:s");
        $result_object = $test_model->save();
        $this->assertEquals("Thru\\ActiveRecord\\Test\\Models\\TestModel", get_class($result_object));

        $this->assertEquals($test_model->integer_field, $result_object->integer_field);
        $this->assertEquals($test_model->text_field, $result_object->text_field);
        $this->assertEquals($test_model->date_field, $result_object->date_field);

        $this->assertGreaterThan(0, $test_model->test_model_id, "Verify updated old object id");
        $this->assertGreaterThan(0, $result_object->test_model_id, "Verify new object id");
        $this->assertEquals($test_model->test_model_id, $result_object->test_model_id, "Verify new and old are same");
    }

    public function testSearchOneResult()
    {
      /* @var $test_model TestModel */
      /* @var $result_object TestModel */
        $test_model = TestModel::factory();
        $test_model->integer_field = $this->faker->numberBetween(0, 9999999);
        $test_model->text_field = $this->faker->paragraph(5);
        $test_model->date_field = $this->faker->date("Y-m-d H:i:s");
        $test_model->save();

        $this->assertEquals(1, TestModel::search()->count());

        $result_object = TestModel::search()->where('test_model_id', $test_model->test_model_id)->execOne();
        $this->assertEquals("Thru\\ActiveRecord\\Test\\Models\\TestModel", get_class($result_object));
        $this->assertEquals($test_model->integer_field, $result_object->integer_field);
        $this->assertEquals($test_model->text_field, $result_object->text_field);
        $this->assertEquals($test_model->date_field, $result_object->date_field);
        $this->assertEquals(1, $result_object->test_model_id);

        return $test_model;
    }

    public function testSearchZeroResults()
    {
        $this->assertEquals(false, TestModel::search()->execOne());
        $this->assertEquals(array(), TestModel::search()->exec());
    }

    public function testSearchInvalid()
    {
        $this->assertFalse(TestModel::search()->where('test_model_id', -1)->execOne());
    }

  /**
   * @depends testSearchOneResult
   * @param \Thru\ActiveRecord\Test\Models\TestModel $test_model
   */
    public function testLabels(TestModel $test_model)
    {
        $this->assertEquals("No label for Thru\\ActiveRecord\\Test\\Models\\TestModel ID 1", $test_model->get_label());

        $with_name_label = new \Thru\ActiveRecord\Test\Models\TestModelWithNameLabel();
        $with_name_label->name = "Wrong name here";
        $with_name_label->something_else = "Right name here";
        $with_name_label->save();

        $this->assertEquals($with_name_label->something_else, $with_name_label->get_label(), "Name label works");
    }

    public function testLabelAutoName()
    {
        $model = new TestModel();
        $model->name = 'bar';
        $this->assertEquals("bar", $model->get_label());
    }

    public function testLabelAutoDescription()
    {
        $model = new TestModel();
        $model->test_model_id = $this->faker->numberBetween(1, 100000);
        $model->description = 'foo';
        $this->assertEquals("foo", $model->get_label());
    }

    public function testLabelManual()
    {
        $model = new TestModelWithNameLabel();
        $model->something_else = 'foo';
        $this->assertEquals("foo", $model->get_label());
    }

    public function testLabelFailure()
    {
        $model = new TestModel();
        $model->test_model_id = $this->faker->numberBetween(1, 100000);
        $this->assertEquals("No label for Thru\\ActiveRecord\\Test\\Models\\TestModel ID {$model->test_model_id}", $model->get_label());
    }

    public function testUpdate()
    {
        $insert = new TestModel();
        $insert->text_field = "Before";
        $insert->integer_field = 0;
        $insert->date_field = date("Y-m-d H:i:s");
        $insert->save();

        $reload = TestModel::search()->where('test_model_id', $insert->test_model_id)->execOne();

        $this->assertEquals("Before", $reload->text_field);

        $reload->text_field = "After";
        $reload->save();

        $reload_again = TestModel::search()->where('test_model_id', $insert->test_model_id)->execOne();

        $this->assertEquals("After", $reload_again->text_field);

        return $reload_again;
    }

  /**
   * @depends testUpdate
   */
    public function testReturnedTypes(TestModel $result)
    {
        $this->assertTrue(is_integer($result->test_model_id));
        $this->assertTrue(is_integer($result->integer_field));
        $this->assertTrue(is_string($result->text_field));
        $this->assertNotFalse(strtotime($result->date_field));
    }

    public function testDelete()
    {
        $deletable = new TestModel();
        $deletable->text_field = "Before";
        $deletable->integer_field = 0;
        $deletable->date_field = date("Y-m-d H:i:s");
        $deletable->save();

        $this->assertTrue($deletable->delete(), "Delete function returned true");

        return $deletable->test_model_id;
    }

  /**
   * @depends testDelete
   */
    public function testDeleteVerify($test_model_id)
    {
        $reload = TestModel::search()->where('test_model_id', $test_model_id)->execOne();
        $this->assertFalse($reload, "Delete verified");
    }

  /**
   * @depends testUpdate
   */
    public function testGetClass(TestModel $testModel)
    {
        $this->assertEquals("Thru\\ActiveRecord\\Test\\Models\\TestModel", $testModel->get_class(false));
        $this->assertEquals("TestModel", $testModel->get_class(true));
    }

    public function testCreateTableOnSearch()
    {
        $this->assertEquals(0, TestModelSearchOnly::search()->count());

        TestModelSearchOnly::delete_table();
    }

    public function testQueryCache()
    {
        $insert = new TestModel();
        $insert->text_field = "Before";
        $insert->integer_field = 0;
        $insert->date_field = date("Y-m-d H:i:s");
        $insert->save();

      // Run once
        $first_time = microtime(true);
        $first = TestModel::search()->where('test_model_id', 1)->execOne();
        $first_time = microtime(true) - $first_time;

      // Run twice
        $second_time = microtime(true);
        $second = TestModel::search()->where('test_model_id', 1)->execOne();
        $second_time = microtime(true) - $second_time;

        $this->assertEquals($first, $second, "Both are identitical");
        $this->assertLessThanOrEqual($first_time, $second_time, "Query cache did indeed speed up loading");
    }

    public function testActiveRecordNoKey()
    {
        $model = new TestModelNoKey();
        $model->a = "foo";
        $model->b = "bar";
        $model->save();

        $all = TestModelNoKey::search()->exec();
        $this->assertTrue(is_array($all));
        $this->assertEquals("Thru\\ActiveRecord\\Test\\Models\\TestModelNoKey", get_class(reset($all)));
    }

    public function testActiveRecordReloadUnsaved()
    {
        $model = new TestModel();
        $this->assertFalse($model->reload());
    }



  /**
   * TODO: flesh this out
   */
    public function testTableBad()
    {
        $model = new TestModelBad();
    }

    public function testStoreObject()
    {
        $object = new \StdClass();
        $object->foo = "bar";

        $different_object = new \StdClass();
        $different_object->hello = "there";

        $model = new TestModel();
        $model->date_field = date("Y-m-d H:i:s");
        $model->integer_field = 1;
        $model->text_field = $object;
        $model->save();

        $reload = TestModel::search()->where('test_model_id', $model->test_model_id)->execOne();
        $this->assertEquals(JsonPrettyPrinter::Json($object), $reload->text_field);
      // TODO: This should really be returning a deserialised blob.

        $reload->text_field = $different_object;
        $reload->save();

        $reload_again = TestModel::search()->where('test_model_id', $model->test_model_id)->execOne();
        $this->assertEquals(JsonPrettyPrinter::Json($different_object), $reload_again->text_field);
    }

  /**
   * @expectedExceptionMessage Unknown table 'test_models'
   * @expectedException \Thru\ActiveRecord\DatabaseLayer\TableDoesntExistException
   */
    public function testDestroyTableThatDoesntExist()
    {
        $model = new TestModel();
        $model->delete_table();
        $vq = new VirtualQuery();
        $interpreter = $vq->getInterpreter();
        $interpreter->destroyTable($model);
    }

    public function testSetTable()
    {
        $testModel = new TestModel();
        $this->assertEquals("test_models", $testModel->get_database_table());

        $testModel->set_database_table("something_else");
        $this->assertEquals("something_else", $testModel->get_database_table());

        $testModel = new TestModel();
        $this->assertEquals("test_models", $testModel->get_database_table());
    }

    public function testValueIsNull()
    {
        $testModelNullable = new TestModelNullable();
        $testModelNullable->integer_field = 1;
        $testModelNullable->text_field_nullable = null;
        $testModelNullable->date_field = date(TestModel::$MYSQL_FORMAT);
        $testModelNullable->save();

        $this->assertEquals(null, $testModelNullable->text_field_nullable);

        return $testModelNullable;
    }

  /**
   * @depends testValueIsNull
   */
    public function testValueIsNullUpdatable(TestModelNullable $testModelNullable)
    {
        $testModelNullable->text_field_nullable = "hello";
        $testModelNullable->save();
        $this->assertEquals("hello", $testModelNullable->text_field_nullable);
        $testModelNullable->text_field_nullable = null;
        $testModelNullable->save();
        $testModelNullable->reload();
        $this->assertNull($testModelNullable->text_field_nullable);
    }
}
