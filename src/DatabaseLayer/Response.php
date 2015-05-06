<?php
/**
 * Created by PhpStorm.
 * User: geusebio
 * Date: 06/05/15
 * Time: 19:34
 */

namespace Thru\ActiveRecord\DatabaseLayer;


class Response {

	public $result;
	public $delay;
	public $error;

	public function __construct($result, $error, $delay, $query){
		$this->result = $result;
		$this->delay = $delay;
		$this->error = $error;
		$this->query = $query;
	}

	public function get_error_exception(){
		if($this->error->code == "42S02"){
			return new TableDoesntExistException($this->error->info[2]);
		}
		return new Exception("Uncaught Database Exception: {$this->error->info[2]}", $this->error->code);
	}

	public function is_error(){
		if(intval($this->error->code) != 0){
			return true;
		}
		return false;
	}
}