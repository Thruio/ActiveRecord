<?php
/**
 * Created by PhpStorm.
 * User: Baggett
 * Date: 09/02/2015
 * Time: 11:29
 *
 * Partially Borrowed from Drupal 8 UUID tests:
 * https://github.com/drupal/drupal/blob/8.0.x/core/tests/Drupal/Tests/Component/Uuid/UuidTest.php
 */

use \Thru\ActiveRecord\UUID;
class UUIDTest extends PHPUnit_Framework_TestCase {
  const uuid_format = "";

  /**
   * Tests UUID validation.
   *
   * @param string $uuid
   *   The uuid to check against.
   * @param bool $is_valid
   *   Whether the uuid is valid or not.
   * @param string $message
   *   The message to display on failure.
   *
   * @dataProvider providerTestValidation
   */
  public function testValidation($uuid, $is_valid, $message) {
    $this->assertSame($is_valid, Uuid::is_valid($uuid), $message);
  }

  /**
   * Dataprovider for UUID instance tests.
   *
   * @return array
   *  An array of arrays containing
   *   - The Uuid to check against.
   *   - (bool) Whether or not the Uuid is valid.
   *   - Failure message.
   */
  public function providerTestValidation() {
    return array(
      // These valid UUIDs.
      array('6ba7b810-9dad-11d1-80b4-00c04fd430c8', TRUE, 'Basic FQDN UUID did not validate'),
      array('00000000-0000-0000-0000-000000000000', TRUE, 'Minimum UUID did not validate'),
      array('ffffffff-ffff-ffff-ffff-ffffffffffff', TRUE, 'Maximum UUID did not validate'),
      // These are invalid UUIDs.
      array('0ab26e6b-f074-4e44-9da-601205fa0e976', FALSE, 'Invalid format was validated'),
      array('0ab26e6b-f074-4e44-9daf-1205fa0e9761f', FALSE, 'Invalid length was validated'),
    );
  }

  public function testGeneratedUUIDv3NotUnique(){
    $namespace = UUID::v4();
    $name = "test";
    $this->assertEquals(UUID::v3($namespace, $name), UUID::v3($namespace, $name), 'Same UUID v5 was generated twice.');
  }

  public function testGeneratedUUIDv4Unique(){
    $this->assertNotEquals(UUID::v4(), UUID::v4(), 'Same UUID was not generated twice.');
  }

  public function testGeneratedUUIDv5NotUnique(){
    $namespace = UUID::v4();
    $name = "test";
    $this->assertEquals(UUID::v5($namespace, $name), UUID::v5($namespace, $name), 'Same UUID v5 was generated twice.');
  }

  public function testGenerateUUIDs(){
    $namespace = UUID::v4();
    $name = "test";
    $this->assertTrue(UUID::is_valid(UUID::v3($namespace, $name)), 'UUID v3 generation works.');
    $this->assertTrue(UUID::is_valid(UUID::v4()), 'UUID v4 generation works.');
    $this->assertTrue(UUID::is_valid(UUID::v5($namespace, $name)), 'UUID v5 generation works.');
  }

  public function testGeneratedUUIDNamespaces(){
    $this->assertFalse(UUID::v3("garbage", "test"));
    $this->assertFalse(UUID::v5("garbage", "test"));
    $this->assertNotFalse(UUID::v3("00000000-0000-0000-0000-000000000000", "test"));
    $this->assertNotFalse(UUID::v5("00000000-0000-0000-0000-000000000000", "test"));
  }
}
