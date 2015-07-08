<?php

namespace Thru\ActiveRecord\Test\Models;

use Thru\ActiveRecord\ActiveRecord;

/**
 * Class TestModelWithNameLabel
 * @var $test_model_with_label_id integer
 * @var $name text
 * @var $something_else text
 */
class TestModelWithNameLabel extends ActiveRecord
{

    protected $_table = "test_models_with_name_labels";

    protected $_label_column = "something_else";

    public $test_model_with_label_id;
    public $name;
    public $something_else;
}
