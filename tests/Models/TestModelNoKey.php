<?php

namespace Thru\ActiveRecord\Test\Models;

use Thru\ActiveRecord\ActiveRecord;

/**
 * Class TestModelNoKey
 * @var $a text
 * @var $b text
 */
class TestModelNoKey extends ActiveRecord
{

    protected $_table = "test_models_no_key";

    public $a;
    public $b;
}
