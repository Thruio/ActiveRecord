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

    public function postConstruct()
    {
        parent::postConstruct();
        $this->sequence = intval($this->sequence);
    }

    public function save($automatic_reload = true)
    {
        $databaseLayer = DatabaseLayer::getInstance();
        $lockController = $databaseLayer->lockController($this->getTable(), $this->getTableAlias());

        // Check the table exists.
        $this->getTableBuilder()->build();

        // Lock the table.
        $lockController->lock();

        // Get our primary key
        $primaryColumn = $this->getIDField();

        // Get the highest primary key
        if (!$this->$primaryColumn) {
            $highest = DumbModel::query("SELECT max({$primaryColumn}) as highest FROM {$this->getTable()}");
            $highestKey = end($highest)->highest;

            // Set our primary key to this +1
            $newKey = isset($highestKey)?$highestKey + 1 : 1;
            $this->$primaryColumn = $newKey;
            // echo "{$this->get_table()}: {$primaryColumn} = {$newKey}\n";
        }

        // Set sequence to sequence + 1
        $highestSequence = DumbModel::query("SELECT max(sequence) as highest FROM {$this->getTable()} WHERE `{$primaryColumn}` = '{$this->$primaryColumn}'");
        $highestSequenceKey = end($highestSequence)->highest;
        if (!$highestSequenceKey) {
            $this->sequence = 1;
        } else {
            $this->sequence = intval($highestSequenceKey) + 1;
        }

        // Save the object
        parent::save($automatic_reload);

        // Unlock the table.
        $lockController->unlock();

        // return the object.
        return $this;
    }

    public function delete(){
        $update = new DatabaseLayer\Update($this->_table);
        $update->setData(['deleted' => 'Yes']);
        $update->condition($this->getIDField(), $this->getId());
        $update->condition('sequence', $this->sequence);
        $update->execute();
        #echo "Deleting {$this->_table} with id = {$this->getId()}, sequence = {$this->sequence}\n";

        $this->reload();
        return $this;
    }

}
