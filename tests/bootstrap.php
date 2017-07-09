<?php

// ensure we get report on all possible php errors
error_reporting(-1);

require __DIR__ . '/defines.php';
require __DIR__ . '/../loader.php';
require __DIR__ . '/../framework/includes/init.php';
require __DIR__ . '/../framework/includes/common_functions.php';

# Register test classes
$autoloader->addPrefix('Kazinduzi\Tests', __DIR__);
