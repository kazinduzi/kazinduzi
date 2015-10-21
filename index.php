<?php
/**
 * This file allows your kazinduzi application to work without .htaccess
 * by using the following url: http://www.yourhost.com/index.php/path/to/your/app
 *
 * This is discouraged over using a proper .htaccess rewrite.
 *
 * ONCE .htaccess IS NOT WORKING, DO UN-COMMENT THESE LINES OF CODE BELOW.
 */
/**
 * APPLICATION ENVIRONMENT
 *
 * Setting the environment for logging and error reporting.
 */
define('ENVIRONMENT', $_SERVER['APPLICATION_ENV'] ? $_SERVER['APPLICATION_ENV'] : 'development');
defined('KAZINDUZI_START_TIME') || define('KAZINDUZI_START_TIME', microtime(true));
defined('KAZINDUZI_START_MEMORY') || define('KAZINDUZI_START_MEMORY', memory_get_usage());

defined('DS') || define('DS', DIRECTORY_SEPARATOR);
defined('PS') || define('PS', PATH_SEPARATOR);
defined('EXT') || define('EXT', '.php');
defined('CLASS_EXT') || define('CLASS_EXT', '.class.php');
defined('MODEL_EXT') || define('MODEL_EXT', '.model.php');

/** define the site path */
define('BASE_PATH', __DIR__);
defined('KAZINDUZI_PATH') || define('KAZINDUZI_PATH', BASE_PATH . '/framework');
defined('APP_PATH') || define('APP_PATH', BASE_PATH . '/application');
defined('CORE_PATH') || define('CORE_PATH', realpath(KAZINDUZI_PATH . '/core'));
defined('LIB_PATH') || define('LIB_PATH', realpath(KAZINDUZI_PATH . '/library'));
defined('DB_PATH') || define('DB_PATH', realpath(KAZINDUZI_PATH . '/database'));
defined('WIDGETS_PATH') || define('WIDGETS_PATH', realpath(APP_PATH . '/widgets'));

defined('LAYOUT_PATH') || define('LAYOUT_PATH', realpath(KAZINDUZI_PATH . DIRECTORY_SEPARATOR . 'elements/layouts'));
defined('THEME_PATH') || define('THEME_PATH', realpath(APP_PATH . DIRECTORY_SEPARATOR . 'themes'));
defined('CONTROLLERS_PATH') || define('CONTROLLERS_PATH', realpath(APP_PATH . DIRECTORY_SEPARATOR . 'controllers'));
defined('VIEWS_PATH') || define('VIEWS_PATH', realpath(APP_PATH . DIRECTORY_SEPARATOR . 'views'));
defined('MODELS_PATH') || define('MODELS_PATH', realpath(APP_PATH . DIRECTORY_SEPARATOR . 'models'));
defined('MODULES_PATH') || define('MODULES_PATH', APP_PATH . DIRECTORY_SEPARATOR . 'modules');
defined('VENDOR_PATH') || define('VENDOR_PATH', KAZINDUZI_PATH . DIRECTORY_SEPARATOR . 'vendor');

defined('CURRENT_URL') || define('CURRENT_URL', (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
defined('HOME_URL') || define('HOME_URL', (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']);
defined('SITE_URL') || define('SITE_URL', '/');
defined('KAZINDUZI_DEBUG') || define('KAZINDUZI_DEBUG', true);

/** Include first the automatic loader for classes and other */
$includePaths = array(
    APP_PATH,
    MODULES_PATH,
    VENDOR_PATH
);

array_push($includePaths, get_include_path());
set_include_path(join(PATH_SEPARATOR, $includePaths));

require_once __DIR__ . '/loader.php';
require_once __DIR__ . '/kazinduzi.php';
require_once KAZINDUZI_PATH . '/includes/init.php';
require_once KAZINDUZI_PATH . '/includes/common_functions.php';

if (is_file(__DIR__ . '/INSTALL_LOCK')) {    
    redirect('/install/index.php');
    die();
}

/**
 * ! IMPORTANT NOTICE !
 * -----------------------------------------------------------------------------
 * Put this before bootstrapping. Because this will work session_start();
 * which is always on the top of the file.
 * Here we are using the custom session storage.
 * It is used as default storage || database storage
 */
$session = Kazinduzi::session();
$session->start();
require_once APP_PATH . DIRECTORY_SEPARATOR . 'bootstrap.php';
