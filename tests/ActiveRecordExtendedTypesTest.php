<?php
/**
 * Created by PhpStorm.
 * User: Baggett
 * Date: 09/02/2015
 * Time: 15:33
 */

use \Thru\ActiveRecord\Test\TestModelExtendedTypes;

class ActiveRecordExtendedTypesTest extends PHPUnit_Framework_TestCase {

  /* @var $extended TestModelExtendedTypes */
  private $extended;

  public function setUp(){
    $this->extended = new TestModelExtendedTypes();
  }

  public function tearDown(){

    #TestModelExtendedTypes::factory()->get_table_builder()->destroy();
  }

  public function testExtendedTypes(){
    /* @var $result TestModelExtendedTypes */
    $this->extended->blob_field = file_get_contents(dirname(__FILE__) . "/../vendor/bin/phpunit");
    $this->extended->decimal_field = 12345.67890;
    $this->extended->enum_field = "Yes";
    $this->extended->md5_field = md5($this->extended->blob_field);
    $this->extended->sha1_field = sha1($this->extended->blob_field);
    $this->extended->uuid_field = \Thru\ActiveRecord\UUID::v4();
    $result = $this->extended->save();

    $this->assertEquals($this->extended->blob_field, $result->blob_field);
    $this->assertEquals($this->extended->decimal_field, $result->decimal_field);
    $this->assertEquals($this->extended->enum_field, $result->enum_field);
    $this->assertEquals($this->extended->md5_field, $result->md5_field);
    $this->assertEquals($this->extended->sha1_field, $result->sha1_field);
    $this->assertEquals($this->extended->uuid_field, $result->uuid_field);
  }


}
