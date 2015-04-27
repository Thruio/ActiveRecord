<?php

namespace Thru\ActiveRecord\Test;

use Thru\ActiveRecord\Test\Models\TestModel;

class ActiveRecordExportTest extends \PHPUnit_Framework_TestCase {
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
    $this->assertArrayHasKey("_columns", $model_array);
    $this->assertEquals("test_models", $model_array['_table']);
    $this->assertEquals(null, $model_array['test_model_id']);
    $this->assertEquals($test_model->integer_field, $model_array['integer_field']);
    $this->assertEquals($test_model->text_field, $model_array['text_field']);
    $this->assertEquals($test_model->date_field, $model_array['date_field']);
    $this->assertEquals(null, $model_array['_label_column']);
    $this->assertEquals("test_model_id", $model_array['_columns'][0]);
    $this->assertEquals("integer_field", $model_array['_columns'][1]);
    $this->assertEquals("text_field", $model_array['_columns'][2]);
    $this->assertEquals("date_field", $model_array['_columns'][3]);

    $model_array_filtered = $test_model->__toArray($test_model->_calculate_save_down_rows());

    $this->assertArrayNotHasKey("_table", $model_array_filtered);
    $this->assertArrayHasKey("test_model_id", $model_array_filtered);
    $this->assertArrayHasKey("integer_field", $model_array_filtered);
    $this->assertArrayHasKey("text_field", $model_array_filtered);
    $this->assertArrayHasKey("date_field", $model_array_filtered);
    $this->assertArrayNotHasKey("_label_column", $model_array_filtered);
    $this->assertArrayNotHasKey("_columns", $model_array_filtered);
    $this->assertEquals(4, count($model_array_filtered));
  }

  public function testModelToPublicArray(){
    /* @var $test_model TestModel */
    $test_model = TestModel::factory();
    $test_model->integer_field = rand(0, 9999999);
    $test_model->text_field = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur congue nulla orci, consectetur faucibus augue feugiat finibus. Vivamus efficitur odio odio, id blandit diam porttitor condimentum. Nulla a quam augue. Phasellus at velit non diam posuere ultrices non ut felis. Fusce id ornare arcu. Duis nisl ipsum, vestibulum eget turpis eu, aliquam lobortis lectus. Mauris semper ipsum ullamcorper, aliquam purus quis, commodo nibh. Maecenas gravida, enim vitae posuere dignissim, orci leo tincidunt magna, a iaculis risus nulla ut arcu. Donec ullamcorper fermentum dui nec ullamcorper. Sed at finibus turpis. Ut ex justo, porta eu augue a, pulvinar ultrices elit. Suspendisse eget dolor accumsan, consequat nisi sed, dictum tortor. Etiam aliquet metus eget nunc placerat convallis. Ut ut nisl quis ante egestas auctor.\nSed luctus urna velit. Vestibulum sagittis eros sem, sit amet tristique mi varius et. Fusce ultrices tincidunt elit eget varius. Aliquam tincidunt suscipit urna bibendum dapibus. Aliquam lorem tortor, cursus sit amet fringilla a, blandit auctor diam. Vestibulum vel tincidunt dui. Pellentesque molestie aliquet enim rhoncus volutpat.\nUt varius sollicitudin posuere. Vestibulum rutrum leo urna, in dictum nisi consectetur sit amet. Aliquam consectetur nunc sed ipsum ullamcorper, id scelerisque sapien gravida. Phasellus nec mattis augue. Praesent consequat mauris ut mauris varius rutrum. Maecenas viverra ex nec varius cursus. Sed non rhoncus lorem.\nIn at lorem a nisi sollicitudin ornare eget at ligula. Ut sit amet odio fringilla, sollicitudin nunc vitae, viverra arcu. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Donec aliquet urna sit amet justo egestas, eget consectetur dui vulputate. Nam vitae lacinia magna, quis lobortis dolor. Sed id scelerisque felis. Ut porttitor sodales pulvinar. Morbi tincidunt varius velit in volutpat. Morbi malesuada sollicitudin sem, pulvinar sodales lacus congue sed. Praesent convallis aliquam porttitor. Donec cursus nisi eu mattis fermentum. Ut ut luctus lectus, a pretium neque. Nullam vel aliquam turpis.\nCras vitae efficitur sem. Quisque interdum neque lobortis lacus tincidunt, quis placerat augue condimentum. Morbi condimentum laoreet nunc id aliquam. Aenean elit ligula, ullamcorper vitae erat et, fermentum vehicula nisl. Praesent mattis massa tempor vehicula euismod. Integer sed orci et ex vulputate molestie. Fusce vel porta quam. Interdum et malesuada fames ac ante ipsum primis in faucibus. Praesent suscipit, lacus id iaculis molestie, lacus purus tempus neque, lobortis efficitur dolor sem in diam. Maecenas maximus ac orci auctor tristique. Nam lectus velit, egestas et molestie placerat, luctus nec magna. Curabitur dignissim blandit ullamcorper. Suspendisse sed libero vel dui imperdiet viverra. Mauris bibendum, magna ut imperdiet suscipit, nunc arcu blandit metus, interdum molestie enim diam sit amet lorem. Ut mauris ipsum, varius in risus vel, condimentum turpis duis.";
    $test_model->date_field = date("Y-m-d H:i:s");

    $model_array = $test_model->__toPublicArray();

    // Verify what should/should not come through
    $this->assertArrayNotHasKey("_table", $model_array);
    $this->assertArrayHasKey("test_model_id", $model_array);
    $this->assertArrayHasKey("integer_field", $model_array);
    $this->assertArrayHasKey("text_field", $model_array);
    $this->assertArrayHasKey("date_field", $model_array);
    $this->assertArrayNotHasKey("_label_column", $model_array);
    $this->assertArrayNotHasKey("_columns", $model_array);

    // Verify Data.
    $this->assertEquals(null, $model_array['test_model_id']);
    $this->assertEquals($test_model->integer_field, $model_array['integer_field']);
    $this->assertEquals($test_model->text_field, $model_array['text_field']);
    $this->assertEquals($test_model->date_field, $model_array['date_field']);

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
    $this->assertTrue(property_exists($decoded, "_columns"));

    $this->assertEquals("test_models", $decoded->_table);
    $this->assertEquals(null, $decoded->test_model_id);
    $this->assertEquals($test_model->integer_field, $decoded->integer_field);
    $this->assertEquals($test_model->text_field, $decoded->text_field);
    $this->assertEquals($test_model->date_field, $decoded->date_field);
    $this->assertEquals(null, $decoded->_label_column);
    $this->assertEquals("test_model_id", $decoded->_columns[0]);
    $this->assertEquals("integer_field", $decoded->_columns[1]);
    $this->assertEquals("text_field", $decoded->_columns[2]);
    $this->assertEquals("date_field", $decoded->_columns[3]);
  }

}
