<?php

$testDatabases['mysql'] = array(
  'db_type' => 'Mysql',
  'db_hostname' => 'localhost',
  'db_port' => '3306',
  'db_username' => 'travis',
  'db_password' => 'travis',
  'db_database' => 'active_record_test',
);

$testDatabases['postgres'] = array(
  'db_type' => 'Postgres',
  'db_hostname' => 'localhost',
  'db_port' => '5432',
  'db_username' => 'travis',
  'db_password' => 'travis',
  'db_database' => 'active_record_test',
);

$testDatabases['sqlite'] = array(
  'db_type' => 'Sqlite',
  'db_file' => 'test.sqlite',
);

if (file_exists('test.sqlite')) {
    unlink('test.sqlite');
}
