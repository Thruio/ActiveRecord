<?php

namespace Thru\ActiveRecord\Test\Models;

use Thru\ActiveRecord\ActiveRecord;

/**
 * Class TestModel
 * @var $a foo
 * @var $b bar
 */
class TestModelBad extends ActiveRecord{

  protected $_table = "test_model_bad";

  public $a;
  public $b;

}
