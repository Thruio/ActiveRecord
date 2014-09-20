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
      if(function_exists("apc_add")){
        apc_add("SearchIndex[{$table}][{$key}]", $object, 60);
      }
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
      }elseif(function_exists('apc_exists') && apc_exists("SearchIndex[{$table}][{$key}]")){
        return apc_fetch("SearchIndex[{$table}][{$key}]");
      }else{
        return false;
      }
    }

    public function expire($table, $key){
      if($this->exists($table, $key)){
        unset($this->index[$table][$key]);
        if(function_exists('apc_delete')){
          apc_delete("SearchIndex[{$table}][{$key}]");
        }
        return true;
      }else{
        return false;
      }
    }
}