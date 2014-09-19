<?php

namespace FourOneOne\ActiveRecord;

class DumbModel
{
    static public function query($query, $type = 'StdClass', $key_by = null)
    {
        $db = DatabaseLayer::get_instance();
        $passthru = $db->passthru($query);
        return $passthru->execute($type);
    }

    static public function queryOne($query, $type = 'StdClass')
    {
        $result = self::query($query, $type);
        return end($result);
    }
}