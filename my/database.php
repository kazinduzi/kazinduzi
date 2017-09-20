<?php

defined('KAZINDUZI_PATH') || exit('No direct script access allowed');

$config = [
    'driver' => 'Mysqli',
    'persistent' => true,
    'db_host' => 'db',
    'db_port' => '3306',
	'db_user' => 'root',
	'db_password' => 'password',
	'db_name' => 'kazinduzi',
    'db_prefix' => '',
    'debug' => true,
    'cache_on' => false,
    'cache_dir' => false,
    'auto_init' => true,
    'auto_shutdown' => true,
    'strict_on' => false,
];
return $config;
