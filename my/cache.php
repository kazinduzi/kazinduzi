<?php 
defined('KAZINDUZI_PATH') || exit('No direct script access allowed');

//# Memcache configuration
//$config = [
//    'driver' => 'memcached',
//    'servers' => [
//        [
//            'host' => '127.0.0.1',
//            'port' => 11211,
//            'persistent' => false,
//            'weight' => 1,
//            'timeout' => 1,
//            'retry_interval' => 15,
//            'status' => true,
//            'failure_callback' => null
//        ],
//        # Add another server here, if you have another one
//    ],
//    'prefix' => 'kazinduzi_',
//];
//
# Memcached configuration 
//$config = [
//    'driver' => 'memcached',
//    'servers' => [
//        [
//            'host' => '127.0.0.1',
//            'port' => 11211,
//        ],
//        # Add another server here, if you have another one
//    ],
//    'prefix' => 'spoorverledendrachten_',
//];

# Redis cache configuration
$config = [
    'driver' => 'Redis',
    'prefix' => 'kazinduzi_',
];

//# XCache cache configuration
//$config = [
//    'driver' => 'Xcache',
//    'prefix' => 'kazinduzi_',
//];
//
//# Filesystem caching
//$config = [
//    'driver' => 'file',
//    'cache_dir' => APP_PATH . DIRECTORY_SEPARATOR . 'cache',
//    'ttl' => 1800,
//    'requests' => 1000,
//    'prefix' => 'kazinduzi_',
//];

return $config;