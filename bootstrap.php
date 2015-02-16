<?php

require_once("vendor/autoload.php");

$database = new \Thru\ActiveRecord\DatabaseLayer(array(
  'db_type'     => 'Mysql',
  'db_hostname' => '10.0.0.100',
  'db_port'     => '3306',
  'db_username' => 'travis',
  'db_password' => 'travis',
  'db_database' => 'active_record_test'
));

