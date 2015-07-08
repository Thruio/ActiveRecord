<?php
namespace Thru\ActiveRecord\Test;

use Thru\ActiveRecord\DatabaseLayer;
use Thru\ActiveRecord\DatabaseLayer\VirtualQuery;
use \Thru\ActiveRecord\Test\Models\TestModel;
use \Thru\ActiveRecord\Test\Models\TestModelExtendedTypes;
use Thru\ActiveRecord\Test\Models\TestModelNullable;
use \Thru\ActiveRecord\Test\Models\TestModelWithNameLabel;
use \Thru\ActiveRecord\Test\Models\TestModelSortable;
use \Thru\ActiveRecord\Test\Models\TestModelSearchOnly;
use \Thru\ActiveRecord\Test\Models\TestModelNoKey;
use \Thru\ActiveRecord\Test\Models\TestModelBad;
use Thru\ActiveRecord\Test\Models\TestVersionedModel;
use \Thru\JsonPrettyPrinter\JsonPrettyPrinter;
use \Faker;

class VersionedActiveRecordTest extends BaseTest
{

    public function tearDown()
    {
      #TestVersionedModel::delete_table();
    }

    public function testCreateVersionedRecord()
    {
        $versionedRecord = new TestVersionedModel();
        $versionedRecord->value = "blue";
        $versionedRecord->save();
        $this->assertGreaterThan(0, $versionedRecord->id);
        $this->assertEquals(1, $versionedRecord->sequence);
        return $versionedRecord;
    }

  /**
   * @depends testCreateVersionedRecord
   */
    public function testUpdateVersionedRecord(TestVersionedModel $versionedRecord)
    {
        $originalSequence = $versionedRecord->sequence;
        $originalId = $versionedRecord->id;

        $versionedRecord->value = "red";
        $versionedRecord->save();

        $this->assertEquals($originalId, $versionedRecord->id, "ID did not change");
        $this->assertNotEquals($originalSequence, $versionedRecord->sequence, "But the sequence did");
        $this->assertEquals($originalSequence+1, $versionedRecord->sequence, "infact, it incremented by 1.");
    }

    public function testVersionedRecordDoesNotHaveAutoIncrement()
    {
        $tvm = new TestVersionedModel();
        $util = new DatabaseLayer\Util();
        $keys = $util->getIndexes($tvm->get_database_table());
        $failed = false;
        foreach ($keys as $key) {
            if ($key->Auto_increment) {
                $failed = true;
            }
        }
        $this->assertFalse($failed, "One of the keys for a Versioned record is autoincrementing.");
    }

    public function testUpdateVersionedRecordDoesNotChangeID()
    {
        $barry = new TestVersionedModel();
        $oliver = new TestVersionedModel();

      // Round 1.
        $barry->value = "cat";
        $barry->save();
        $oliver->value = "ford";
        $oliver->save();
        $barry_original_id = $barry->id;
        $oliver_original_id = $oliver->id;
        $this->assertEquals("cat", $barry->value);
        $this->assertEquals("ford", $oliver->value);

      // Round 2.
        $barry->value = "dog";
        $barry->save();
        $this->assertEquals("dog", $barry->value);

      // Round 3.
        $barry->value = "mouse";
        $barry->save();
        $this->assertEquals("mouse", $barry->value);
        $oliver->value = "chevrolet";
        $oliver->save();
        $this->assertEquals("chevrolet", $oliver->value);

      // Verify.
        $this->assertEquals($barry_original_id, $barry->id);
        $this->assertEquals($oliver_original_id, $oliver->id);
    }
}
