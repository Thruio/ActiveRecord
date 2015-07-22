<?php

require_once "vendor/autoload.php";
require_once "test_configs.php";

use Monolog\Formatter as LogFormatter;
use Monolog\Handler as LogHandler;
use Monolog\Logger;

$fileLoggerHandler = new LogHandler\StreamHandler(__DIR__ . "/build/logs/active-record." . date('Y-m-d') . '.log', null, null, 0664);
$monologHandlers = [$fileLoggerHandler];
$monolog = new Logger("ActiveRecord", $monologHandlers);

if (!isset($env)) {
    if (!getenv('DB')) {
        $env = "mysql";
    } else {
        $env = getenv("DB");
    }
}


if (!isset($testDatabases[$env])) {
    die("Invalid environment [$env]\n");
}
$database = new \Thru\ActiveRecord\DatabaseLayer($testDatabases[$env]);


$database->setLogger($monolog);
