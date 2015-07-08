<?php

namespace Thru\ActiveRecord;

class DumbModel
{
    public static function query($query, $type = 'StdClass', $key_by = null)
    {
        $database = DatabaseLayer::get_instance();
        $passthru = $database->passthru($query);
        return $passthru->execute($type);
    }

    public static function queryOne($query, $type = 'StdClass')
    {
        $result = self::query($query, $type);
        return end($result);
    }
}
