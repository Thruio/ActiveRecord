<?php

namespace Thru\ActiveRecord\Test;

use Thru\ActiveRecord\ActiveRecord;

/**
 * Class TestModelExtendedTypes
 * @var $test_model_id integer
 * @var $enum_field enum("Yes", "No")
 * @var $blob_field blob
 * @var $decimal_field decimal(10,5)
 * @var $uuid_field uuid
 * @var $md5_field md5
 * @var $sha1_field sha1
 */
class TestModelExtendedTypes extends ActiveRecord{

  protected $_table = "test_models_extended_types";

  public $test_model_id;
  public $enum_field;
  public $blob_field;
  public $decimal_field;
  public $uuid_field;
  public $md5_field;
  public $sha1_field;

}
