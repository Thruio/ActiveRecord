<?php
/**
 * Created by PhpStorm.
 * User: Matthew Baggett
 * Date: 15/02/2015
 * Time: 20:43
 */

namespace Thru\ActiveRecord\Test;

use Thru\ActiveRecord\DatabaseLayer\Table;
use Thru\ActiveRecord\Test\TestModel;
use Thru\ActiveRecord\DatabaseLayer\TableBuilder;

class TableTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructTableWithAlias()
    {
        $table = new Table("table_name", "table_alias");
        $this->assertEquals("table_name", $table->getName());
        $this->assertEquals("table_alias", $table->getAlias());
    }

    public function testConstructTableWithoutAlias()
    {
        $table = new Table("table_name");
        $this->assertEquals("table_name", $table->getName());
        $this->assertEquals("tn", $table->getAlias());
    }

    public function testConstructTableWithAliasWithReservedWord()
    {
        $table = new Table("apples_delta_dookie");
        $this->assertEquals("apples_delta_dookie", $table->getName());
        $this->assertEquals("a_d_d", $table->getAlias());
    }

    /**
     * @expectedException \Thru\ActiveRecord\DatabaseLayer\TableDestroyFailureException
     */
    /*public function testDoubleDestroyTable(){
      $test_model = new TestModel();
      $table_builder = new TableBuilder($test_model);
      $table_builder->destroy();
      $table_builder->destroy();
    }*/
}
