<?php
namespace Thru\ActiveRecord;

use Thru\ActiveRecord\DatabaseLayer\Response;

class Exception extends \Exception
{

  /** @var Response $response */
  protected $response;

  public function __construct($message = null, $code = null, \Exception $previous = null, Response $response = null){
    $this->response = $response;
    $code = intval($code);
    parent::__construct($message, $code, $previous);
  }

  public function getResponse(){
    return $this->response;
  }

    public function __toString() {
        return parent::__toString() . "\n" . var_export($this->getResponse(),true);
    }
}