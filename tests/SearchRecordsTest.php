<?php
/**
 * Created by PhpStorm.
 * User: geusebio
 * Date: 09/02/15
 * Time: 20:32
 */

use \Thru\ActiveRecord\Test\TestModelWithNameLabel;
use \Thru\ActiveRecord\Test\TestModelSortable;

class SearchRecordsTest extends PHPUnit_Framework_TestCase {
  public function testSearchBySlug(){
    $slug_original = new TestModelWithNameLabel();
    $slug_original->name = "Example";
    $slug_original->save();

    $this->assertStringMatchesFormat("%d-example", $slug_original->get_slug());

    $slug_searched = TestModelWithNameLabel::get_by_slug($slug_original->get_slug());

    $this->assertNotFalse($slug_searched);

    $this->assertEquals($slug_original->test_model_with_label_id, $slug_searched->test_model_with_label_id);
  }

  public function testSearchOrder(){
    $one = new TestModelSortable();
    $two = new TestModelSortable();
    $three = new TestModelSortable();

    $one->integer_field = 3;
    $two->integer_field = 2;
    $three->integer_field = 1;

    $one->text_field = "Dog";
    $two->text_field = "Parakeet";
    $three->text_field = "Cat";

    $one->date_field = "1990-06-01 04:00:00";
    $two->date_field = "1983-01-08 00:00:00";
    $three->date_field = date("Y-m-d H:i:s", strtotime("tomorrow"));

    $one->save();
    $two->save();
    $three->save();

    $sort_by_num_desc = TestModelSortable::search()->order("integer_field", "DESC")->exec();
    $sort_by_num_asc = TestModelSortable::search()->order("integer_field", "ASC")->exec();

    $sort_by_text_desc = TestModelSortable::search()->order("text_field", "DESC")->exec();
    $sort_by_text_asc = TestModelSortable::search()->order("text_field", "ASC")->exec();

    $sort_by_date_desc = TestModelSortable::search()->order("date_field", "DESC")->exec();
    $sort_by_date_asc = TestModelSortable::search()->order("date_field", "ASC")->exec();

    $this->assertEquals(1, $one->test_model_id);
    $this->assertEquals(2, $two->test_model_id);
    $this->assertEquals(3, $three->test_model_id);

    $this->assertEquals(array(1,2,3), array_keys($sort_by_num_desc));
    $this->assertEquals(array(3,2,1), array_keys($sort_by_num_asc));
    $this->assertEquals(array(2,1,3), array_keys($sort_by_text_desc));
    $this->assertEquals(array(3,1,2), array_keys($sort_by_text_asc));
    $this->assertEquals(array(3,1,2), array_keys($sort_by_date_desc));
    $this->assertEquals(array(2,1,3), array_keys($sort_by_date_asc));

  }
}
