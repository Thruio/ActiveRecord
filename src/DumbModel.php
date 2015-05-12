<?php

namespace Thru\ActiveRecord;

class DumbModel
{
    static public function query($query, $type = 'StdClass', $key_by = null)
    {
        $database = DatabaseLayer::get_instance();
        $passthru = $database->passthru($query);

        return $passthru->execute($type);
    }

    static public function queryOne($query, $type = 'StdClass')
    {
        $result = self::query($query, $type);

        return end($result);
    }
}