<?php
namespace Thru\ActiveRecord;

class Exception extends \Exception
{

  public function __construct($message = null, $int = null, \Exception $previous = null){
    parent::__construct($message, $int, $previous);
  }
}