<?php
defined('KAZINDUZI_PATH') || exit('No direct script access allowed');

$config['cookie_name'] = 'kazinduzi_cookie';
$config['session_name'] = 'kazinduzi_session';
$config['session_autostart'] = false;  // ALWAYS false VOOR SECURITY MATTERS
$config['session_match_ip'] = 1;
$config['session_match_useragent'] = 0;
$config['session_lifetime'] = 0;      // The default value 0 means "until the browser is closed."
$config['session_httponly'] = true;
$config['session_cookie_path'] = '';
$config['session_domain'] = '';
$config['session_secure'] = false;
$config['hash_function'] = 'SHA256';
$config['timeout'] = '86400'; // 1 day

#
//$config['type'] = 'database';    // 'database' if you want to use database to save session
//$config['session_db_name'] = 'kazinduzi';

#
$config['type'] = 'default';     // 'default' use the temporary file session store
#
//$config['type'] = 'apc';         // 'default' use the temporary file session store
#
//$config['type'] = 'file';

#### Memcache ######
//=====================
//$config['type'] = 'memcache';
//$config['servers'] = [
//    [
//        'host' => '127.0.0.1',
//        'port' => 11211,
//        'persistent' => false,
//        'weight' => 1,
//        'timeout' => 1,
//        'retry_interval' => 15,
//        'status' => true,
//        'failure_callback' => null
//    ],
//];
//$config['compression'] = false;
//$config['compatibility'] = false;

#### Memcached ######
//=====================
//$config['type'] = 'memcached';
//$config['servers'] = [
//    [
//        'host' => '127.0.0.1',
//        'port' => 11211
//    ]     
//];

return $config;
