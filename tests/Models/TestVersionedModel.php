<?php

namespace Thru\ActiveRecord\Test\Models;

use Thru\ActiveRecord\VersionedActiveRecord;

/**
 * Class TestVersionedModel
 * @var $id INTEGER
 * @var $value TEXT
 */
class TestVersionedModel extends VersionedActiveRecord{

  protected $_table = "test_models_versioned";

  public $id;
  public $value;
}
