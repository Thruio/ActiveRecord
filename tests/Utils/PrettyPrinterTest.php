<?php
/**
 * Created by PhpStorm.
 * User: Baggett
 * Date: 09/02/2015
 * Time: 11:29
 */

use \Thru\ActiveRecord\PrettyPrinter;
class PrettyPrinterTest extends PHPUnit_Framework_TestCase {
  private $example_object;

  public function setUp(){
    $this->example_object = new StdClass();
    $this->example_object->foo = 'bar';
    $this->example_object->string = 'String Here';
    $this->example_object->int = 6293;
    $this->example_object->object = new StdClass();
    $this->example_object->object->inner = 'inner';
    $this->example_object->string_with_newline = "Newline\r\nYo.";
    $this->example_object->string_with_umlaut = "Umläut.";
  }

  public function testPrettyPrinter(){
    $expected_encoded = file_get_contents(dirname(__FILE__) . "/expected.json");
    $actual_encoded = PrettyPrinter::Json($this->example_object);
    $this->assertEquals($expected_encoded, $actual_encoded);
    $this->assertNotFalse(json_decode($actual_encoded));
  }

  public function testJunkyJson(){
    $junky_json = '{"foo": "bar"\r\n}';
    $expected_encoded = file_get_contents(dirname(__FILE__) . "/junky.json");
    $actual_encoded = PrettyPrinter::Json($junky_json);
    $this->assertEquals($expected_encoded,$actual_encoded, "Junky json works as expected. Junk tho'.");
  }
}
