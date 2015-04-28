<?php
namespace Thru\ActiveRecord;

class SearchIndex
{
    private static $instance;

    private $index;

    public static function get_instance(){
      if(!self::$instance instanceof SearchIndex){
        self::$instance = new SearchIndex();
      }
      return self::$instance;
    }

    public function put($table, $key, ActiveRecord $object){
      $this->index[$table][$key] = $object;
      return $this;
    }

    public function exists($table, $key){
      if(isset($this->index[$table][$key])){
        return true;
      }else{
        return false;
      }
    }

    public function get($table, $key){
      if($this->exists($table, $key)){
        return $this->index[$table][$key];
      }else{
        return false;
      }
    }

    public function expire($table, $key){
      if($this->exists($table, $key)){
        unset($this->index[$table][$key]);
        return true;
      }else{
        return false;
      }
    }

    public function wipe(){
      $this->index = [];
      return true;
    }
}