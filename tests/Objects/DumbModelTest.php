<?php
namespace Thru\ActiveRecord\Test;

use Thru\ActiveRecord\DatabaseLayer;
use Thru\ActiveRecord\DumbModel;
use Thru\ActiveRecord\Test\Models\TestModel;
use Faker;

class DumbModelTest extends \PHPUnit_Framework_TestCase
{

    /**
 * @var $faker \Faker\Generator 
*/
    private $faker;

    public function setUp()
    {
        $this->faker = Faker\Factory::create();
        $this->faker->addProvider(new Faker\Provider\Company($this->faker));
        $this->faker->addProvider(new Faker\Provider\Lorem($this->faker));
        $this->faker->addProvider(new Faker\Provider\DateTime($this->faker));

        $model = new TestModel();
        $model->integer_field = $this->faker->numberBetween(1, 10000);
        $model->text_field = $this->faker->paragraph(5);
        $model->date_field = $this->faker->date("Y-m-d H:i:s");
        $model->save();
    }

    public function tearDown()
    {
        TestModel::factory()->getTableBuilder()->destroy();
    }

    public function testDumbModelFetch()
    {
        $result = DumbModel::query("SELECT * FROM " . TestModel::factory()->getTableName());
        $this->assertTrue(is_array($result));
        $this->assertEquals("stdClass", get_class(end($result)));
    }

    public function testDumbModelFetchOne()
    {
        $result = DumbModel::queryOne("SELECT * FROM " . TestModel::factory()->getTableName());
        $this->assertEquals("stdClass", get_class($result));
        return $result;
    }

    /**
   * @depends testDumbModelFetchOne
   */
    public function testDumbModelResponse($result)
    {
        $this->assertTrue(property_exists($result, 'test_model_id'));
        $this->assertTrue(property_exists($result, 'integer_field'));
        $this->assertTrue(property_exists($result, 'text_field'));
        $this->assertTrue(property_exists($result, 'date_field'));
    }

    /**
   * @expectedException \Thru\ActiveRecord\DatabaseLayer\TableDoesntExistException
   */
    public function testTableExistsGotcha()
    {
        $result = DumbModel::queryOne("SELECT * FROM doesntexist", 'Thru\ActiveRecord\Test\Models\NotStdClass');
    }
}
