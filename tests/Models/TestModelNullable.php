<?php

namespace Thru\ActiveRecord\Test\Models;

use Thru\ActiveRecord\ActiveRecord;

/**
 * Class TestModelNullable
 * @var $test_model_id integer
 * @var $integer_field integer
 * @var $text_field_nullable text nullable
 * @var $date_field date
 */
class TestModelNullable extends ActiveRecord
{

    protected $_table = "test_models_nullable";

    public $test_model_id;
    public $integer_field;
    public $text_field_nullable;
    public $date_field;
}
