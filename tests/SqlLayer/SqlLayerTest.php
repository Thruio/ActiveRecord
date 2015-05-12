<?php
namespace Thru\ActiveRecord\Test;

use \Thru\ActiveRecord\Test\Models\TestModel;
use \Thru\ActiveRecord\Test\Models\TestModelExtendedTypes;
use \Thru\ActiveRecord\Test\Models\TestModelWithNameLabel;
use \Thru\ActiveRecord\Test\Models\TestModelSortable;
use \Thru\ActiveRecord\Test\Models\TestModelSearchOnly;
use \Thru\ActiveRecord\DatabaseLayer\VirtualQuery;
use \Thru\ActiveRecord\DatabaseLayer\IndexException;
use Thru\ActiveRecord\Test\Models\BogusVirtualQuery;

class SqlLayerMysqlTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var $base \Thru\ActiveRecord\DatabaseLayer\Sql\Base
     * @var $mysql \Thru\ActiveRecord\DatabaseLayer\Sql\Mysql
     */
    private $base;
    private $mysql;

    public function setUp()
    {
        $this->base  = new \Thru\ActiveRecord\DatabaseLayer\Sql\Base();
        $this->mysql = new \Thru\ActiveRecord\DatabaseLayer\Sql\Mysql();
        TestModel::delete_table();
        TestModelExtendedTypes::delete_table();
        TestModelWithNameLabel::delete_table();
        TestModelSortable::delete_table();
        TestModelSearchOnly::delete_table();
    }

    /**
     * @expectedException         \Thru\ActiveRecord\Exception
     * @expectedExceptionMessage  Operation BogusVirtualQuery not supported
     */
    public function testProcessMySqlExceptionOnInvalidOperation()
    {
        $bogus_virtual_query = new BogusVirtualQuery();
        $this->mysql->process($bogus_virtual_query);
    }

    /**
     * @expectedException         \Thru\ActiveRecord\Exception
     * @expectedExceptionMessage  Base::process should have been overridden
     */
    public function testProcessBaseExceptionOnInvalidOperation()
    {
        $bogus_virtual_query = new BogusVirtualQuery();
        $this->base->process($bogus_virtual_query);
    }

    public function testIndex()
    {
        $model                = new TestModelSortable();
        $model->integer_field = 1;
        $model->text_field    = "Feh";
        $model->date_field    = date("Y-m-d H:i:s");
        $model->save();

        $mysql   = new \Thru\ActiveRecord\DatabaseLayer\Sql\Mysql();
        $indexes = $mysql->getIndexes("test_models_sortable");
        $this->assertTrue(is_array($indexes));
        $this->assertTrue($indexes[0]->Column_name == "test_model_id");
    }

    /**
     * @expectedException \Thru\ActiveRecord\DatabaseLayer\IndexException
     */
    public function testIndexFailOnNonExistant()
    {
        $mysql = new \Thru\ActiveRecord\DatabaseLayer\Sql\Mysql();
        $mysql->getIndexes("test_models_sortable");
    }

    public function testPassthru()
    {
        $query    = "SELECT \"hello\" as `col`";
        $passthru = new \Thru\ActiveRecord\DatabaseLayer\Passthru();
        $passthru->query($query);

        $this->assertEquals($query, $passthru->get_sql_to_passthru());

        $result = $passthru->execute();

        $expected      = new \StdClass();
        $expected->col = "hello";
        $this->assertEquals($expected, reset($result));
    }

    /**
     * @expectedException \Thru\ActiveRecord\DatabaseLayer\Exception
     */
    public function testPassthruInvalidSQL()
    {
        $query    = "this isn't the sql you're looking for.";
        $passthru = new \Thru\ActiveRecord\DatabaseLayer\Passthru();
        $passthru->query($query);

        $this->assertEquals($query, $passthru->get_sql_to_passthru());

        $passthru->execute();
    }


}

