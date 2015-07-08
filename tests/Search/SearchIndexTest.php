<?php
namespace Thru\ActiveRecord\Test;

use \Thru\ActiveRecord\SearchIndex;
use \Thru\ActiveRecord\Test\Models\TestModel;

class SearchIndexTest extends \PHPUnit_Framework_TestCase
{
    public function testSearchIndexSuccess()
    {
        $dummy_object = new TestModel();
        $dummy_object->test_model_id = 90;
        $dummy_object->text_field = "Bloo bee blah";
        $dummy_object->date_field = "1990-06-01 04:00:00";
        $insertion = SearchIndex::getInstance()->put('nontable', 'nonkey', $dummy_object);
        $this->assertEquals("Thru\\ActiveRecord\\SearchIndex", get_class($insertion));
        $this->assertTrue(SearchIndex::getInstance()->exists('nontable', 'nonkey'));

        $fetch = SearchIndex::getInstance()->get('nontable', 'nonkey');
        $this->assertEquals("Thru\\ActiveRecord\\Test\\Models\\TestModel", get_class($fetch));
        $this->assertEquals($dummy_object->test_model_id, $fetch->test_model_id);
        $this->assertEquals($dummy_object->text_field, $fetch->text_field);
        $this->assertEquals($dummy_object->date_field, $fetch->date_field);
    }

    public function testSearchIndexFailure()
    {
        $this->assertFalse(SearchIndex::getInstance()->exists('nontable', 'doesntexist'));
        $this->assertFalse(SearchIndex::getInstance()->get('nontable', 'doesntexist'));
    }

    public function testSearchIndexDelete()
    {
        $this->assertTrue(SearchIndex::getInstance()->exists('nontable', 'nonkey'));
        SearchIndex::getInstance()->expire('nontable', 'nonkey');
        $this->assertFalse(SearchIndex::getInstance()->exists('nontable', 'nonkey'));
    }

    public function testWipe()
    {
        $this->assertTrue(SearchIndex::getInstance()->wipe());
    }
}
