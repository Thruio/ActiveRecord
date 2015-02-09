<?php

namespace Thru\ActiveRecord\Test;

use Thru\ActiveRecord\ActiveRecord;

/**
 * Class TestModelWithLabel
 * @var $test_model_with_label_id integer
 * @var $name text
 */
class TestModelWithNameLabel extends ActiveRecord{

  protected $_table = "test_models_with_name_labels";

  public $test_model_with_label_id;
  public $name;

}