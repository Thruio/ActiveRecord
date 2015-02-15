<?php
/**
 * Created by PhpStorm.
 * User: Baggett
 * Date: 09/02/2015
 * Time: 15:33
 */

use \Thru\ActiveRecord\Test\TestModel;
use \Thru\ActiveRecord\Test\TestModelExtendedTypes;
use \Thru\ActiveRecord\Test\TestModelWithNameLabel;
use \Thru\ActiveRecord\Test\TestModelSortable;
use \Thru\ActiveRecord\Test\TestModelSearchOnly;
use \Thru\UUID;

class ActiveRecordExtendedTypesTest extends PHPUnit_Framework_TestCase {

  /* @var $extended TestModelExtendedTypes */
  private $extended;

  public function setUp(){
    $this->extended = new TestModelExtendedTypes();
  }

  public function tearDown(){
    TestModel::delete_table();
    TestModelExtendedTypes::delete_table();
    TestModelWithNameLabel::delete_table();
    TestModelSortable::delete_table();
    TestModelSearchOnly::delete_table();
  }

  public function testExtendedTypes(){
    /* @var $result TestModelExtendedTypes */
    $this->extended->blob_field = file_get_contents(dirname(__FILE__) . "/../../vendor/bin/phpunit");
    $this->extended->decimal_field = 12345.67890;
    $this->extended->enum_field = "Yes";
    $this->extended->md5_field = md5($this->extended->blob_field);
    $this->extended->sha1_field = sha1($this->extended->blob_field);
    $this->extended->uuid_field = UUID::v4();
    $this->extended->string_with_length_field = "blah";
    $result = $this->extended->save();

    $this->assertEquals($this->extended->blob_field, $result->blob_field);
    $this->assertEquals($this->extended->decimal_field, $result->decimal_field);
    $this->assertEquals($this->extended->enum_field, $result->enum_field);
    $this->assertEquals($this->extended->md5_field, $result->md5_field);
    $this->assertEquals($this->extended->sha1_field, $result->sha1_field);
    $this->assertEquals($this->extended->uuid_field, $result->uuid_field);
    $this->assertEquals($this->extended->string_with_length_field, $result->string_with_length_field);
  }


}
