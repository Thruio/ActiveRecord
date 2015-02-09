<?php
/**
 * Created by PhpStorm.
 * User: Baggett
 * Date: 09/02/2015
 * Time: 15:33
 */

use \Thru\ActiveRecord\ActiveRecord;
use \Thru\ActiveRecord\Test\TestModel;
use Thru\ActiveRecord\Test\TestModelWithNameLabel;
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
    echo "Deleting object #{$deletable->test_model_id}\n";
    $this->assertTrue($deletable->delete(), "Delete function returned true");

    return $deletable->test_model_id;
  }

  /**
   * @depends testDelete
   */
  public function testDeleteVerify($test_model_id){
    $reload = TestModel::search()->where('test_model_id', $test_model_id)->execOne();
    var_dump($reload);
    $this->assertFalse($reload , "Delete verified");
  }
}
