<?php

require_once("vendor/autoload.php");

use Monolog\Logger;
use Monolog\Handler as LogHandler;
use Monolog\Formatter as LogFormatter;

$fileLoggerHandler = new LogHandler\StreamHandler(__DIR__ . "/build/logs/active-record." . date('Y-m-d') . '.log', null, null, 0664);
$monologHandlers = [$fileLoggerHandler];
$monolog = new Logger("ActiveRecord", $monologHandlers);

$database = new \Thru\ActiveRecord\DatabaseLayer(array(
  'db_type'     => 'Mysql',
  'db_hostname' => 'localhost',
  'db_port'     => '3306',
  'db_username' => 'travis',
  'db_password' => 'travis',
  'db_database' => 'active_record_test',
));

$database->setLogger($monolog);

