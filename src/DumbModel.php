<?php

namespace Thru\ActiveRecord;

class DumbModel
{
    public static function query($query, $type = 'StdClass', $key_by = null)
    {
        $database = DatabaseLayer::getInstance();
        $passthru = $database->passthru($query);
        return $passthru->execute($type);
    }

    public static function queryOne($query, $type = 'StdClass')
    {
        $result = self::query($query, $type);
        return end($result);
    }
}
