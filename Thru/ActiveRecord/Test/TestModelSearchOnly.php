<?php

namespace Thru\ActiveRecord\Test;

use Thru\ActiveRecord\ActiveRecord;

/**
 * Class TestModelSearchOnly
 * @var $test_model_id integer
 * @var $integer_field integer
 * @var $text_field text
 * @var $date_field date
 */
class TestModelSearchOnly extends ActiveRecord{

  protected $_table = "test_models_search_only";

  public $test_model_id;
  public $integer_field;
  public $text_field;
  public $date_field;

}
