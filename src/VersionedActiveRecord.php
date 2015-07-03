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

  public function __post_construct(){
    parent::__post_construct();
    $this->sequence = intval($this->sequence);
  }

  public function save($automatic_reload = true){
    $databaseLayer = DatabaseLayer::get_instance();
    $lockController = $databaseLayer->lockController($this->get_table(), $this->get_table_alias());

    // Check the table exists.
    $this->get_table_builder()->build();

    // Lock the table.
    $lockController->lock();

    // Get our primary key
    $primaryColumn = $this->get_primary_key_index()[0];

    // Get the highest primary key
    if(!$this->$primaryColumn) {
      $highest = DumbModel::query("SELECT max({$primaryColumn}) as highest FROM {$this->get_table()}");
      $highestKey = end($highest)->highest;
      if (!$highestKey) {
        $highestKey = 1;
      }

      // Set our primary key to this +1
      $newKey = $highestKey + 1;
      $this->$primaryColumn = $newKey;
      #echo "{$this->get_table()}: {$primaryColumn} = {$newKey}\n";
    }

    // Set sequence to sequence + 1
    \Kint::dump("SELECT max(sequence) as highest FROM {$this->get_table()} WHERE `{$primaryColumn}` = '{$this->$primaryColumn}'");
    $highestSequence = DumbModel::query("SELECT max(sequence) as highest FROM {$this->get_table()} WHERE `{$primaryColumn}` = '{$this->$primaryColumn}'");
    $highestSequenceKey = end($highestSequence)->highest;
    \Kint::dump($highestSequence);
    if (!$highestSequenceKey) {
      $this->sequence = 1;
    }else{
      $this->sequence = intval($highestSequenceKey) + 1;
    }

    // Save the object
    parent::save($automatic_reload);

    // Unlock the table.
    $lockController->unlock();

    // return the object.
    return $this;
  }
}