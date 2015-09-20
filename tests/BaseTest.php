<?php
namespace Thru\ActiveRecord\Test;

use Faker\Generator;
use Faker\Provider;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{

    /**
 * @var $faker Generator
*/
    protected $faker;
    const TIME_TEST_FORMAT = "%d-%d-%d %d:%d:%d";
    const TIME_STORAGE_FORMAT = "Y-m-d H:i:s";

    /**
   * Setup the test environment.
   *
   * @return void
   */
    public function setUp()
    {
        parent::setUp();
        $this->faker = \Faker\Factory::create();
        $this->faker->addProvider(new Provider\DateTime($this->faker));
        $this->faker->addProvider(new Provider\Company($this->faker));
        $this->faker->addProvider(new Provider\Lorem($this->faker));
        $this->faker->addProvider(new Provider\DateTime($this->faker));
    }

    public function tearDown()
    {

    }
}
