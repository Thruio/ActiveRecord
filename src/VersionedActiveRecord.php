<?php
namespace Thru\ActiveRecord;

use Thru\ActiveRecord\DatabaseLayer;

/**
 * Class VersionedObject
 * @package TigerKit\Models
 * @var $deleted ENUM("Yes","No")
 * @var $sequence INTEGER
 */
abstract class VersionedActiveRecord extends ActiveRecord
{
  public $deleted = "No";
  public $sequence = 0;

  public $_is_versioned = true;

  public function save($automatic_reload = true){
    $databaseLayer = DatabaseLayer::get_instance();
    $lockController = $databaseLayer->lockController($this->get_table(), $this->get_table_alias());

    // Lock the table.
    $lockController->lock();

    // Get our primary key
    $primaryColumn = $this->get_primary_key_index()[0];

    // Get the highest primary key
    $highest = DumbModel::query("SELECT max({$primaryColumn}) as highest FROM {$this->get_table()}");
    $highestKey = end($highest)->highest;
    if(!$highestKey){
      $highestKey = 1;
    }

    // Set our primary key to this +1
    $newKey = $highestKey + 1;
    $this->$primaryColumn = $newKey;
    #echo "{$this->get_table()}: {$primaryColumn} = {$newKey}\n";

    // Set sequence to sequence + 1
    $this->sequence = $this->sequence + 1;

    // Save the object
    #\Kint::dump($this->__toArray());
    $object = parent::save($automatic_reload);

    // Unlock the table.
    $lockController->unlock();

    // return the object.
    return $object;
  }

  /**
   * @return User|false
   */
  public function getCreatedUser(){
    if(!$this->_created_user){
      $this->_created_user = User::search()->where('user_id', $this->created_user_id)->execOne();
    }
    return $this->_created_user;
  }

  /**
   * @return User|false
   */
  public function getUpdatedUser(){
    if(!$this->_updated_user){
      $this->_updated_user = User::search()->where('user_id', $this->updated_user_id)->execOne();
    }
    return $this->_updated_user;
  }
}