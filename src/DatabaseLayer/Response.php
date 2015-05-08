<?php

namespace Thru\ActiveRecord\DatabaseLayer;

use Thru\ActiveRecord\Exception;

class Response {

	public $result;
	public $delay;
	public $error;
    public $query_type;

	public function __construct($result = null, $error = null, $delay = null, $query = null){
		$this->result = $result;
		$this->delay = $delay;
		$this->error = $error;
		$this->query = $query;
	}

	public function get_error_exception(){
		if($this->error->code == "42S02"){
			return new TableDoesntExistException($this->error->info[2], $this->error->code, null, $this);
		}
		return new Exception("Uncaught Database Exception: {$this->error->info[2]}", $this->error->code, null, $this);
	}

	public function is_error(){
		if(intval($this->error->code) != 0){
			return true;
		}
		return false;
	}
}