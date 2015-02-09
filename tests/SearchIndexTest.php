<?php
/**
 * Created by PhpStorm.
 * User: geusebio
 * Date: 09/02/15
 * Time: 19:05
 */

use \Thru\ActiveRecord\SearchIndex;
use \Thru\ActiveRecord\Test\TestModel;

class SearchIndexTest extends PHPUnit_Framework_TestCase {
  public function testSearchIndexSuccess(){
    $dummy_object = new TestModel();
    $dummy_object->test_model_id = 90;
    $dummy_object->text_field = "Bloo bee blah";
    $dummy_object->date_field = "1990-06-01 04:00:00";
    $insertion = SearchIndex::get_instance()->put('nontable','nonkey', $dummy_object);
    $this->assertEquals("Thru\\ActiveRecord\\SearchIndex", get_class($insertion));
    $this->assertTrue(SearchIndex::get_instance()->exists('nontable','nonkey'));

    $fetch = SearchIndex::get_instance()->get('nontable','nonkey');
    $this->assertEquals("Thru\\ActiveRecord\\Test\\TestModel", get_class($fetch));
    $this->assertEquals($dummy_object->test_model_id, $fetch->test_model_id);
    $this->assertEquals($dummy_object->text_field, $fetch->text_field);
    $this->assertEquals($dummy_object->date_field, $fetch->date_field);
  }

  public function testSearchIndexFailure(){
    $this->assertFalse(SearchIndex::get_instance()->exists('nontable','doesntexist'));
    $this->assertFalse(SearchIndex::get_instance()->get('nontable','doesntexist'));
  }

  public function testSearchIndexDelete(){
    $this->assertTrue(SearchIndex::get_instance()->exists('nontable','nonkey'));
    SearchIndex::get_instance()->expire('nontable','nonkey');
    $this->assertFalse(SearchIndex::get_instance()->exists('nontable','nonkey'));
  }
}
