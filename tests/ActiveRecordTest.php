<?php
/**
 * Created by PhpStorm.
 * User: Baggett
 * Date: 09/02/2015
 * Time: 15:33
 */

use \Thru\ActiveRecord\ActiveRecord;
use \Thru\ActiveRecord\Test\TestModel;
use \Thru\ActiveRecord\Test\TestModelWithNameLabel;

class ActiveRecordTest extends PHPUnit_Framework_TestCase {

  public function testSetup(){
    $models_to_remove = array(
      new TestModel(),
      new TestModelWithNameLabel()
    );

    $dblayer = \Thru\ActiveRecord\DatabaseLayer::get_instance();

    foreach($models_to_remove as $model) {
      $dblayer->passthru("DROP TABLE " . $model->get_table_name())->execute();
    }
  }

  public function testConstruct(){
    $test_model = new TestModel();
    $this->assertEquals("Thru\\ActiveRecord\\Test\\TestModel", get_class($test_model));
    $this->assertTrue(in_array("Thru\\ActiveRecord\\ActiveRecord", class_parents($test_model)));
  }

  public function testSearchEmptyResult(){
    $this->assertEquals(0, TestModel::search()->count());
  }

  public function testCreate(){
    /* @var $test_model TestModel */
    /* @var $result_object TestModel */
    $test_model = TestModel::factory();
    $test_model->integer_field = rand(0, 9999999);
    $test_model->text_field = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur congue nulla orci, consectetur faucibus augue feugiat finibus. Vivamus efficitur odio odio, id blandit diam porttitor condimentum. Nulla a quam augue. Phasellus at velit non diam posuere ultrices non ut felis. Fusce id ornare arcu. Duis nisl ipsum, vestibulum eget turpis eu, aliquam lobortis lectus. Mauris semper ipsum ullamcorper, aliquam purus quis, commodo nibh. Maecenas gravida, enim vitae posuere dignissim, orci leo tincidunt magna, a iaculis risus nulla ut arcu. Donec ullamcorper fermentum dui nec ullamcorper. Sed at finibus turpis. Ut ex justo, porta eu augue a, pulvinar ultrices elit. Suspendisse eget dolor accumsan, consequat nisi sed, dictum tortor. Etiam aliquet metus eget nunc placerat convallis. Ut ut nisl quis ante egestas auctor.\nSed luctus urna velit. Vestibulum sagittis eros sem, sit amet tristique mi varius et. Fusce ultrices tincidunt elit eget varius. Aliquam tincidunt suscipit urna bibendum dapibus. Aliquam lorem tortor, cursus sit amet fringilla a, blandit auctor diam. Vestibulum vel tincidunt dui. Pellentesque molestie aliquet enim rhoncus volutpat.\nUt varius sollicitudin posuere. Vestibulum rutrum leo urna, in dictum nisi consectetur sit amet. Aliquam consectetur nunc sed ipsum ullamcorper, id scelerisque sapien gravida. Phasellus nec mattis augue. Praesent consequat mauris ut mauris varius rutrum. Maecenas viverra ex nec varius cursus. Sed non rhoncus lorem.\nIn at lorem a nisi sollicitudin ornare eget at ligula. Ut sit amet odio fringilla, sollicitudin nunc vitae, viverra arcu. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Donec aliquet urna sit amet justo egestas, eget consectetur dui vulputate. Nam vitae lacinia magna, quis lobortis dolor. Sed id scelerisque felis. Ut porttitor sodales pulvinar. Morbi tincidunt varius velit in volutpat. Morbi malesuada sollicitudin sem, pulvinar sodales lacus congue sed. Praesent convallis aliquam porttitor. Donec cursus nisi eu mattis fermentum. Ut ut luctus lectus, a pretium neque. Nullam vel aliquam turpis.\nCras vitae efficitur sem. Quisque interdum neque lobortis lacus tincidunt, quis placerat augue condimentum. Morbi condimentum laoreet nunc id aliquam. Aenean elit ligula, ullamcorper vitae erat et, fermentum vehicula nisl. Praesent mattis massa tempor vehicula euismod. Integer sed orci et ex vulputate molestie. Fusce vel porta quam. Interdum et malesuada fames ac ante ipsum primis in faucibus. Praesent suscipit, lacus id iaculis molestie, lacus purus tempus neque, lobortis efficitur dolor sem in diam. Maecenas maximus ac orci auctor tristique. Nam lectus velit, egestas et molestie placerat, luctus nec magna. Curabitur dignissim blandit ullamcorper. Suspendisse sed libero vel dui imperdiet viverra. Mauris bibendum, magna ut imperdiet suscipit, nunc arcu blandit metus, interdum molestie enim diam sit amet lorem. Ut mauris ipsum, varius in risus vel, condimentum turpis duis.";
    $test_model->date_field = date("Y-m-d H:i:s");
    $result_object = $test_model->save();
    $this->assertEquals("Thru\\ActiveRecord\\Test\\TestModel", get_class($result_object));

    $this->assertEquals($test_model->integer_field, $result_object->integer_field);
    $this->assertEquals($test_model->text_field, $result_object->text_field);
    $this->assertEquals($test_model->date_field, $result_object->date_field);

    $this->assertGreaterThan(0, $test_model->test_model_id, "Verify updated old object id");
    $this->assertGreaterThan(0, $result_object->test_model_id, "Verify new object id");
    $this->assertEquals($test_model->test_model_id, $result_object->test_model_id, "Verify new and old are same");

    return $test_model;
  }

  /**
   * @depends testCreate
   */
  public function testSearchOneResult($test_model){
    $this->assertEquals(1, TestModel::search()->count());

    $result_object = TestModel::search()->where('test_model_id', 1)->execOne();
    $this->assertEquals("Thru\\ActiveRecord\\Test\\TestModel", get_class($result_object));
    $this->assertEquals($test_model->integer_field, $result_object->integer_field);
    $this->assertEquals($test_model->text_field, $result_object->text_field);
    $this->assertEquals($test_model->date_field, $result_object->date_field);
    $this->assertEquals(1, $result_object->test_model_id);
  }

  public function testSearchInvalid(){
    $this->assertFalse(TestModel::search()->where('test_model_id', -1)->execOne());
  }

  public function testLabels(){
    $result_object = TestModel::search()->where('test_model_id', 1)->execOne();
    $this->assertEquals("No label for Thru\\ActiveRecord\\Test\\TestModel ID 1", $result_object->get_label());

    $with_name_label = new \Thru\ActiveRecord\Test\TestModelWithNameLabel();
    $with_name_label->name = "Label name here";
    $with_name_label->save();

    $this->assertEquals($with_name_label->name, $with_name_label->get_label(), "Name label works");
  }

  public function testUpdate(){
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
  public function testDelete(TestModel $deletable){
    $this->assertTrue($deletable->delete(), "Delete function returned true");

    return $deletable->test_model_id;
  }

  /**
   * @depends testDelete
   */
  public function testDeleteVerify($test_model_id){
    $reload = TestModel::search()->where('test_model_id', $test_model_id)->execOne();
    $this->assertFalse($reload , "Delete verified");
  }

  public function testSearchBySlug(){
    $slug_original = new TestModelWithNameLabel();
    $slug_original->name = "Example";
    $slug_original->save();

    $this->assertStringMatchesFormat("%d-example", $slug_original->get_slug());

    $slug_searched = TestModelWithNameLabel::get_by_slug($slug_original->get_slug());

    $this->assertNotFalse($slug_searched);

    $this->assertEquals($slug_original->test_model_with_label_id, $slug_searched->test_model_with_label_id);
  }

  public function testModelToArray(){
    /* @var $test_model TestModel */
    $test_model = TestModel::factory();
    $test_model->integer_field = rand(0, 9999999);
    $test_model->text_field = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur congue nulla orci, consectetur faucibus augue feugiat finibus. Vivamus efficitur odio odio, id blandit diam porttitor condimentum. Nulla a quam augue. Phasellus at velit non diam posuere ultrices non ut felis. Fusce id ornare arcu. Duis nisl ipsum, vestibulum eget turpis eu, aliquam lobortis lectus. Mauris semper ipsum ullamcorper, aliquam purus quis, commodo nibh. Maecenas gravida, enim vitae posuere dignissim, orci leo tincidunt magna, a iaculis risus nulla ut arcu. Donec ullamcorper fermentum dui nec ullamcorper. Sed at finibus turpis. Ut ex justo, porta eu augue a, pulvinar ultrices elit. Suspendisse eget dolor accumsan, consequat nisi sed, dictum tortor. Etiam aliquet metus eget nunc placerat convallis. Ut ut nisl quis ante egestas auctor.\nSed luctus urna velit. Vestibulum sagittis eros sem, sit amet tristique mi varius et. Fusce ultrices tincidunt elit eget varius. Aliquam tincidunt suscipit urna bibendum dapibus. Aliquam lorem tortor, cursus sit amet fringilla a, blandit auctor diam. Vestibulum vel tincidunt dui. Pellentesque molestie aliquet enim rhoncus volutpat.\nUt varius sollicitudin posuere. Vestibulum rutrum leo urna, in dictum nisi consectetur sit amet. Aliquam consectetur nunc sed ipsum ullamcorper, id scelerisque sapien gravida. Phasellus nec mattis augue. Praesent consequat mauris ut mauris varius rutrum. Maecenas viverra ex nec varius cursus. Sed non rhoncus lorem.\nIn at lorem a nisi sollicitudin ornare eget at ligula. Ut sit amet odio fringilla, sollicitudin nunc vitae, viverra arcu. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Donec aliquet urna sit amet justo egestas, eget consectetur dui vulputate. Nam vitae lacinia magna, quis lobortis dolor. Sed id scelerisque felis. Ut porttitor sodales pulvinar. Morbi tincidunt varius velit in volutpat. Morbi malesuada sollicitudin sem, pulvinar sodales lacus congue sed. Praesent convallis aliquam porttitor. Donec cursus nisi eu mattis fermentum. Ut ut luctus lectus, a pretium neque. Nullam vel aliquam turpis.\nCras vitae efficitur sem. Quisque interdum neque lobortis lacus tincidunt, quis placerat augue condimentum. Morbi condimentum laoreet nunc id aliquam. Aenean elit ligula, ullamcorper vitae erat et, fermentum vehicula nisl. Praesent mattis massa tempor vehicula euismod. Integer sed orci et ex vulputate molestie. Fusce vel porta quam. Interdum et malesuada fames ac ante ipsum primis in faucibus. Praesent suscipit, lacus id iaculis molestie, lacus purus tempus neque, lobortis efficitur dolor sem in diam. Maecenas maximus ac orci auctor tristique. Nam lectus velit, egestas et molestie placerat, luctus nec magna. Curabitur dignissim blandit ullamcorper. Suspendisse sed libero vel dui imperdiet viverra. Mauris bibendum, magna ut imperdiet suscipit, nunc arcu blandit metus, interdum molestie enim diam sit amet lorem. Ut mauris ipsum, varius in risus vel, condimentum turpis duis.";
    $test_model->date_field = date("Y-m-d H:i:s");

    $model_array = $test_model->__toArray();

    $this->assertArrayHasKey("_table", $model_array);
    $this->assertArrayHasKey("test_model_id", $model_array);
    $this->assertArrayHasKey("integer_field", $model_array);
    $this->assertArrayHasKey("text_field", $model_array);
    $this->assertArrayHasKey("date_field", $model_array);
    $this->assertArrayHasKey("_label_column", $model_array);
    $this->assertArrayHasKey("_columns_to_save_down", $model_array);
    $this->assertEquals("test_models", $model_array['_table']);
    $this->assertEquals(null, $model_array['test_model_id']);
    $this->assertEquals($test_model->integer_field, $model_array['integer_field']);
    $this->assertEquals($test_model->text_field, $model_array['text_field']);
    $this->assertEquals($test_model->date_field, $model_array['date_field']);
    $this->assertEquals("name", $model_array['_label_column']);
    $this->assertEquals("test_model_id", $model_array['_columns_to_save_down'][0]);
    $this->assertEquals("integer_field", $model_array['_columns_to_save_down'][1]);
    $this->assertEquals("text_field", $model_array['_columns_to_save_down'][2]);
    $this->assertEquals("date_field", $model_array['_columns_to_save_down'][3]);

    $model_array_save_down_applied = $test_model->__toArray($test_model->_calculate_save_down_rows());

    $this->assertArrayNotHasKey("_table", $model_array_save_down_applied);
    $this->assertArrayHasKey("test_model_id", $model_array_save_down_applied);
    $this->assertArrayHasKey("integer_field", $model_array_save_down_applied);
    $this->assertArrayHasKey("text_field", $model_array_save_down_applied);
    $this->assertArrayHasKey("date_field", $model_array_save_down_applied);
    $this->assertArrayNotHasKey("_label_column", $model_array_save_down_applied);
    $this->assertArrayNotHasKey("_columns_to_save_down", $model_array_save_down_applied);
    $this->assertEquals(4, count($model_array_save_down_applied));
  }

  public function testModelToJson(){
    /* @var $test_model TestModel */
    $test_model = TestModel::factory();
    $test_model->integer_field = rand(0, 9999999);
    $test_model->text_field = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur congue nulla orci, consectetur faucibus augue feugiat finibus. Vivamus efficitur odio odio, id blandit diam porttitor condimentum. Nulla a quam augue. Phasellus at velit non diam posuere ultrices non ut felis. Fusce id ornare arcu. Duis nisl ipsum, vestibulum eget turpis eu, aliquam lobortis lectus. Mauris semper ipsum ullamcorper, aliquam purus quis, commodo nibh. Maecenas gravida, enim vitae posuere dignissim, orci leo tincidunt magna, a iaculis risus nulla ut arcu. Donec ullamcorper fermentum dui nec ullamcorper. Sed at finibus turpis. Ut ex justo, porta eu augue a, pulvinar ultrices elit. Suspendisse eget dolor accumsan, consequat nisi sed, dictum tortor. Etiam aliquet metus eget nunc placerat convallis. Ut ut nisl quis ante egestas auctor.\nSed luctus urna velit. Vestibulum sagittis eros sem, sit amet tristique mi varius et. Fusce ultrices tincidunt elit eget varius. Aliquam tincidunt suscipit urna bibendum dapibus. Aliquam lorem tortor, cursus sit amet fringilla a, blandit auctor diam. Vestibulum vel tincidunt dui. Pellentesque molestie aliquet enim rhoncus volutpat.\nUt varius sollicitudin posuere. Vestibulum rutrum leo urna, in dictum nisi consectetur sit amet. Aliquam consectetur nunc sed ipsum ullamcorper, id scelerisque sapien gravida. Phasellus nec mattis augue. Praesent consequat mauris ut mauris varius rutrum. Maecenas viverra ex nec varius cursus. Sed non rhoncus lorem.\nIn at lorem a nisi sollicitudin ornare eget at ligula. Ut sit amet odio fringilla, sollicitudin nunc vitae, viverra arcu. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Donec aliquet urna sit amet justo egestas, eget consectetur dui vulputate. Nam vitae lacinia magna, quis lobortis dolor. Sed id scelerisque felis. Ut porttitor sodales pulvinar. Morbi tincidunt varius velit in volutpat. Morbi malesuada sollicitudin sem, pulvinar sodales lacus congue sed. Praesent convallis aliquam porttitor. Donec cursus nisi eu mattis fermentum. Ut ut luctus lectus, a pretium neque. Nullam vel aliquam turpis.\nCras vitae efficitur sem. Quisque interdum neque lobortis lacus tincidunt, quis placerat augue condimentum. Morbi condimentum laoreet nunc id aliquam. Aenean elit ligula, ullamcorper vitae erat et, fermentum vehicula nisl. Praesent mattis massa tempor vehicula euismod. Integer sed orci et ex vulputate molestie. Fusce vel porta quam. Interdum et malesuada fames ac ante ipsum primis in faucibus. Praesent suscipit, lacus id iaculis molestie, lacus purus tempus neque, lobortis efficitur dolor sem in diam. Maecenas maximus ac orci auctor tristique. Nam lectus velit, egestas et molestie placerat, luctus nec magna. Curabitur dignissim blandit ullamcorper. Suspendisse sed libero vel dui imperdiet viverra. Mauris bibendum, magna ut imperdiet suscipit, nunc arcu blandit metus, interdum molestie enim diam sit amet lorem. Ut mauris ipsum, varius in risus vel, condimentum turpis duis.";
    $test_model->date_field = date("Y-m-d H:i:s");

    $model_json = $test_model->__toJson();

    $decoded = json_decode($model_json);

    $this->assertNotFalse($decoded);

    $this->assertTrue(property_exists($decoded, "_table"));
    $this->assertTrue(property_exists($decoded, "test_model_id"));
    $this->assertTrue(property_exists($decoded, "integer_field"));
    $this->assertTrue(property_exists($decoded, "text_field"));
    $this->assertTrue(property_exists($decoded, "date_field"));
    $this->assertTrue(property_exists($decoded, "_label_column"));
    $this->assertTrue(property_exists($decoded, "_columns_to_save_down"));

    $this->assertEquals("test_models", $decoded->_table);
    $this->assertEquals(null, $decoded->test_model_id);
    $this->assertEquals($test_model->integer_field, $decoded->integer_field);
    $this->assertEquals($test_model->text_field, $decoded->text_field);
    $this->assertEquals($test_model->date_field, $decoded->date_field);
    $this->assertEquals("name", $decoded->_label_column);
    $this->assertEquals("test_model_id", $decoded->_columns_to_save_down[0]);
    $this->assertEquals("integer_field", $decoded->_columns_to_save_down[1]);
    $this->assertEquals("text_field", $decoded->_columns_to_save_down[2]);
    $this->assertEquals("date_field", $decoded->_columns_to_save_down[3]);

  }

}
