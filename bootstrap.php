<?php

require_once("vendor/autoload.php");

use Monolog\Logger;
use Monolog\Handler as LogHandler;
use Monolog\Formatter as LogFormatter;

$fileLoggerHandler = new LogHandler\StreamHandler(__DIR__ . "/build/logs/active-record." . date('Y-m-d') . '.log', null, null, 0664);
$monologHandlers = [$fileLoggerHandler];
$monolog = new Logger("ActiveRecord", $monologHandlers);

if (!getenv('DB')) {
    $env = "mysql";
} else {
    $env = getenv("DB");
}

switch ($env) {
    case 'mysql':
        $database = new \Thru\ActiveRecord\DatabaseLayer(array(
        'db_type' => 'Mysql',
        'db_hostname' => 'localhost',
        'db_port' => '3306',
        'db_username' => 'travis',
        'db_password' => 'travis',
        'db_database' => 'active_record_test',
        ));
        break;
    case 'sqlite':
        if (file_exists('test.sqlite')) {
            unlink('test.sqlite');
        }
        $database = new \Thru\ActiveRecord\DatabaseLayer(array(
        'db_type' => 'Sqlite',
        'db_file' => 'test.sqlite',
        ));
        break;
    default:
        die("Unsupported DB: {$env}\n");
}

$database->setLogger($monolog);
