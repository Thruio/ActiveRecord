<?php
/**
 * Created by PhpStorm.
 * User: Matthew Baggett
 * Date: 15/02/2015
 * Time: 20:43
 */

namespace tests\DatabaseLayer;

use Thru\ActiveRecord\DatabaseLayer\Table;

class TableTest extends \PHPUnit_Framework_TestCase {

  public function testConstructTableWithAlias(){
    $table = new Table("table_name", "table_alias");
    $this->assertEquals("table_name", $table->getName());
    $this->assertEquals("table_alias", $table->getAlias());
  }
  public function testConstructTableWithoutAlias(){
    $table = new Table("table_name");
    $this->assertEquals("table_name", $table->getName());
    $this->assertEquals("tn", $table->getAlias());
  }
}
