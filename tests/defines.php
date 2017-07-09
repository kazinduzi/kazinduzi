<?php

define('ENVIRONMENT', isset($_SERVER['APPLICATION_ENV']) ? $_SERVER['APPLICATION_ENV'] : 'development');
defined('KAZINDUZI_START_TIME') || define('KAZINDUZI_START_TIME', microtime(true));
defined('KAZINDUZI_START_MEMORY') || define('KAZINDUZI_START_MEMORY', memory_get_usage());

defined('DS') || define('DS', DIRECTORY_SEPARATOR);
defined('PS') || define('PS', PATH_SEPARATOR);
defined('EXT') || define('EXT', '.php');
defined('CLASS_EXT') || define('CLASS_EXT', '.class.php');
defined('MODEL_EXT') || define('MODEL_EXT', '.model.php');

/* define the site path */
define('BASE_PATH', dirname(__DIR__));
defined('KAZINDUZI_PATH') || define('KAZINDUZI_PATH', BASE_PATH . '/framework');
defined('APP_PATH') || define('APP_PATH', BASE_PATH.'/application');
defined('CORE_PATH') || define('CORE_PATH', realpath(KAZINDUZI_PATH . '/core'));
defined('LIB_PATH') || define('LIB_PATH', realpath(KAZINDUZI_PATH . '/library'));
defined('DB_PATH') || define('DB_PATH', realpath(KAZINDUZI_PATH . '/database'));
defined('WIDGETS_PATH') || define('WIDGETS_PATH', realpath(APP_PATH . '/widgets'));

defined('LAYOUT_PATH') || define('LAYOUT_PATH', realpath(KAZINDUZI_PATH . '/elements/layouts'));
defined('THEME_PATH') || define('THEME_PATH', realpath(APP_PATH . '/themes'));
defined('CONTROLLERS_PATH') || define('CONTROLLERS_PATH', realpath(APP_PATH . '/controllers'));
defined('VIEWS_PATH') || define('VIEWS_PATH', realpath(APP_PATH . '/views'));
defined('MODELS_PATH') || define('MODELS_PATH', realpath(APP_PATH . '/models'));
defined('MODULES_PATH') || define('MODULES_PATH', APP_PATH . '/modules');
defined('VENDOR_PATH') || define('VENDOR_PATH', KAZINDUZI_PATH . '/vendor');
defined('KAZINDUZI_DEBUG') || define('KAZINDUZI_DEBUG', true);
