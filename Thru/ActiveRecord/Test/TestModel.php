<?php

namespace Thru\ActiveRecord\Test;

use Thru\ActiveRecord\ActiveRecord;

/**
 * Class TestModel
 * @var $test_model_id integer
 * @var $integer_field integer
 * @var $text_field text
 * @var $date_field date
 */
class TestModel extends ActiveRecord{

  protected $_table = "test_models";

  public $test_model_id;
  public $integer_field;
  public $text_field;
  public $date_field;

}
