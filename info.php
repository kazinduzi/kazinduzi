<?php

$cache = new Memcached();
$cache->addServer('127.0.0.1', 11211);
var_dump($cache);
phpinfo();
