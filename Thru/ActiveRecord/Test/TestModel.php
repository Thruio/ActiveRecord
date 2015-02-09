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

  protected $test_model_id;
  protected $integer_field;
  protected $text_field;
  protected $date_field;

}