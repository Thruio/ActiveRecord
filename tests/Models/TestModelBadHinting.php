<?php

namespace Thru\ActiveRecord\Test\Models;

use Thru\ActiveRecord\ActiveRecord;

/**
 * Class TestModelBadHinting
 * @var $test_model_id integer
 * @var $integer_field integer
 * // No hint for text_field
 * @var $date_field date
 */
class TestModelBadHinting extends ActiveRecord
{

    protected $_table = "test_models";

    public $test_model_id;
    public $integer_field;
    public $text_field;
    public $date_field;

}
