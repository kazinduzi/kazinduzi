<?php

// ensure we get report on all possible php errors
error_reporting(-1);

require_once __DIR__ . '/../loader.php';
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/common_functions.php';

# Register test classes
require __DIR__ . '/../Autoload/src/Autoloader.php';
$autoloader = new \Kazinduzi\Autoload\Autoloader();
$autoloader->addPrefix('Kazinduzi\Tests', __DIR__);
