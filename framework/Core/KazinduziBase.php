<?php

namespace Kazinduzi\Core;

//defined('KAZINDUZI_PATH') || exit('No direct script access allowed');

/*
 * Kazinduzi Framework (http://framework.kazinduzi.com/)
 *
 * @author    Emmanuel Ndayiragije <endayiragije@gmail.com>
 * @link      http://kazinduzi.com
 * @copyright Copyright (c) 2010-2013 Kazinduzi. (http://www.kazinduzi.com)
 * @license   http://kazinduzi.com/page/license MIT License
 * @package   Kazinduzi
 */

/*
 *  Set a liberal script execution time limit
 */
if (function_exists('set_time_limit') && ini_get('safe_mode') == 0) {
    set_time_limit(0);
}

if (ini_get('short_open_tag') != 'On') {
    ini_set('short_open_tag', 'On');
}

ini_set('magic_quotes_sybase', 0);

/*
 ************************************************************
 * Start enabling the compression and set the level
 ************************************************************
 */
if (!ini_get('zlib.output_compression')) { //turn on compression
    ini_set('zlib.output_compression', 1);
}
if (ini_get('zlib.output_compression_level') < 0) {
    ini_set('zlib.output_compression_level', 4);
}

/*
 * Set the error_reporting for the Application according to the
 * actual environment {development | produnction | testing}
 */
mb_internal_encoding('UTF-8');
if (defined('ENVIRONMENT')) {
    switch (ENVIRONMENT) {
        case 'development':
            error_reporting(E_ALL | E_STRICT);
            break;
        case 'testing':
        case 'production':
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
            // error_reporting(0);
            break;
        default:
            exit('The application environment is not set correctly.');
    }
}

use Kazinduzi\Db\Database;
use Kazinduzi\Session\Session;

/**
 * Description of Configure
 * Abstract class to be extended.
 *
 * @author Emmanuel_Leonie
 */
abstract class KazinduziBase
{
    // Release version and codename
    const VERSION = '3.0.2';
    const CODENAME = 'ngoma';
    // Common environment type constants for consistency and convenience
    const PRODUCTION = 1;
    const STAGING = 2;
    const TESTING = 3;
    const DEVELOPMENT = 4;
    // Security check that is added to all generated PHP files
    const FILE_SECURITY = '<?php defined(\'KAZINDUZI_PATH\') or die(\'No direct script access allowed.\');';
    // Format of cache files: header, cache name, and data
    const FILE_CACHE = ':header \n\n// :name\n\n:data\n';

    /**
     *  Application title.
     *
     * @var string
     */
    public static $title;


    public static $language;

    /**
     * variable for Encoding charset, default utf-8.
     *
     * @var string
     */
    public static $encoding = 'UTF-8';

    /**
     * @var string
     */
    public static $environment = self::DEVELOPMENT;

    /**
     * @var bool
     */
    public static $is_cli = false;

    /**
     * @var bool
     */
    public static $is_windows = false;

    /**
     * @var bool
     */
    public static $is_unix = false;

    /**
     * @var boll
     */
    public static $magic_quotes = false;

    /**
     * @var boll
     */
    public static $safe_mode = false;

    /**
     * @var Config config object
     */
    public static $config;

    /**
     * @var bool Has [Kohana::init] been called?
     */
    protected static $init = false;

    /**
     * Include paths that are used to find files.
     *
     * @var array
     */
    protected static $paths = [APP_PATH, KAZINDUZI_PATH];

    /**
     * @var array
     */
    private static $instances = [];

    /**
     * @return type
     */
    public static function init()
    {
        if (static::$init) {
            // Do not allow execution twice
            return;
        }
        /*
         * Kazinduzi is now initialized
         */
        static::$init = true;
        /*
         * Fetch the main configuration data and set the $config variable
         */
        static::$config = !static::$config ? static::config() : static::$config;
        /*
         * Convert config to Array of configs
         */
        $config = static::$config->as_array();
        /*
         * This constant defines whether the application should be in debug mode or not. Defaults to false.
         */
        if (isset($config['debug'])) {
            defined('KAZINDUZI_DEBUG') or define('KAZINDUZI_DEBUG', $config['debug']);
        } else {
            defined('KAZINDUZI_DEBUG') or define('KAZINDUZI_DEBUG', false);
        }
        /*
         * Set default language
         */
        if (isset($config['lang'])) {
            static::$language = $config['lang'];
        }
        /*
         * Set Application name
         */
        if (isset($config['Application.name'])) {
            static::setAppName($config['Application.name']);
        }
        /*
         *
         */
        if (isset($config['charset'])) {
            // Set the encoding charset
            static::setCharset($config['charset']);
        }
        /*
         *
         */
        if (ini_get('register_globals')) {
            // Reverse the effects of register_globals
            static::globals();
        }
        /*
         * Determine if we are running in a command line environment
         */
        static::$is_cli = (PHP_SAPI === 'cli');
        /*
         * Determine if we are running in a Windows environment
         */
        static::$is_windows = (DIRECTORY_SEPARATOR === '\\');

        /*
         * Determine if we are running in a Windows environment
         */
        static::$is_unix = (DIRECTORY_SEPARATOR === '\/');

        /*
         * Determine if we are running in safe mode
         */
        static::$safe_mode = (bool) ini_get('safe_mode');

        if (function_exists('mb_internal_encoding')) {
            mb_internal_encoding(static::getCharset());
        }

        /*
         * Using UTF-8 for everything.
         */
        if (function_exists('iconv_set_encoding') && version_compare(PHP_VERSION, '5.0.6') < 0) {
            iconv_set_encoding('internal_encoding', static::getCharset());
            iconv_set_encoding('output_encoding', static::getCharset());
        } else {
            ini_set('default_charset', 'UTF-8');
        }

        /*
         * Set the Timezone
         */
        isset($config['date.timezone']) ?
        // If date.timezone is set in the configuration, affect it to the app.
        static::setTimeZone($config['date.timezone']) :
        // Else set system timezone to UTC timezone as default
        static::setTimeZone('UTC');
        unset($config);
    }

    /**
     * @return type
     */
    public static function globals()
    {
        // Prevent malicious GLOBALS overload attack
        if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS'])) {
            echo 'Global variable overload attack detected! Request aborted.\n';
            exit(1);
        }
        // Get the variable names of all globals
        $global_variables = array_keys($GLOBALS);

        // Remove the standard global variables from the list
        $global_variables = array_diff(
            $global_variables,
            ['_COOKIE', '_ENV', '_GET', '_FILES', '_POST', '_REQUEST', '_SERVER', '_SESSION', 'GLOBALS']
        );
        // Unset the global variable, effectively disabling register_globals
        foreach ($global_variables as $name) {
            unset($GLOBALS[$name]);
        }
    }

    /**
     * @return string
     */
    public static function getAppName()
    {
        $mainConfig = static::getConfig();
        if (empty(static::$title)) {
            static::$title = $mainConfig['Application.name'];
        }

        return isset(static::$title) ? static::$title : 'Kazinduzi, the PHP web application framework';
    }

    /**
     * @param string $name
     */
    public static function setAppName($name)
    {
        static::$title = empty($name) ? 'Kazinduzi, the PHP web application framework' : $name;
    }

    /**
     * @return \Session object
     */
    public static function session()
    {
        session_name(static::getConfig('session')->get('session_name'));

        return Session::instance(static::getConfig('session')->get('type'));
    }

    /**
     * @return \Database object
     */
    public static function db()
    {
        return Database::getInstance()->clear();
    }

    /**
     * Get the cache instance.
     *
     * @return \Cache object
     */
    public static function cache()
    {
        return \Cache::getInstance();
    }

    /**
     * Get the mailer instance.
     */
    public static function getMailer()
    {
        static $mailer;
        if (!$mailer) {
            $mailer = \Mailer::getInstance();
        }
        $clone = clone $mailer;

        return $clone;
    }

    /**
     * @param mixed $arg
     *
     * @return mixed
     */
    public static function load($arg)
    {
        if (is_string(strtolower($arg)) and class_exists($arg, $autoload = true)) {
            $class = ucfirst($arg);
            if (empty(static::$instances[$arg])) {
                return static::$instances[$arg] = is_subclass_of($class, 'Model') ? $class::model() : new $class();
            } else {
                return static::$instances[$arg];
            }
        }
    }

    /**
     * @param mixed $param
     */
    public static function loadHelper($param)
    {
        if (is_string($param) && @file_exists($helper = KAZINDUZI_PATH.DS.'helpers'.DS.$param.'.class.php')) {
            require_once $helper;
        } else {
            $helpers = @func_get_args();
            foreach ($helpers as $k => $v) {
                require_once KAZINDUZI_PATH.DS.'helpers'.DS.$v.'.class.php';
            }
        }
    }

    /**
     * @param type $group
     *
     * @return type
     */
    public static function getConfig($group = null)
    {
        return static::$config = Config::instance($group);
    }

    /**
     * Or redifine the config($group) static method for fetching config file.
     *
     * @param type $group
     *
     * @return type
     */
    public static function config($group = null)
    {
        return static::$config = Config::instance($group);
    }

    /**
     * @param type $group
     *
     * @return type
     */
    public static function configAsArray($group = null)
    {
        return Config::instance($group)->as_array();
    }

    /**
     * @param type $group
     *
     * @return type
     */
    public static function config_toArray($group = null)
    {
        return Config::instance($group)->toArray();
    }

    /**
     * @return type
     */
    public static function powerby()
    {
        return 'Powered by <a href="http://mvc.emmanuelndayiragije.com/">Kazinduzi PHP Framework</a>.';
    }

    /**
     * @return type
     */
    public static function version()
    {
        return static::VERSION;
    }

    /**
     * @return string the path of the framework
     */
    public static function getFrameworkPath()
    {
        return KAZINDUZI_PATH;
    }

    /**
     * Returns the time zone used by this application.
     * This is a simple wrapper of PHP function date_default_timezone_get().
     *
     * @return string the time zone used by this application.
     *
     * @see http://php.net/manual/en/function.date-default-timezone-get.php
     * @since 1.0.9
     */
    public static function getTimeZone()
    {
        return date_default_timezone_get();
    }

    /**
     * Sets the time zone used by this application.
     * This is a simple wrapper of PHP function date_default_timezone_set().
     *
     * @param string $value the time zone used by this application.
     *
     * @see http://php.net/manual/en/function.date-default-timezone-set.php
     * @since 1.0.9
     */
    public static function setTimeZone($value)
    {
        if (function_exists('date_default_timezone_set')) {
            date_default_timezone_set($value);
        }
    }

    /**
     * Set the system encoding charset if is set in configuration.
     *
     * @param type $value
     */
    public static function setCharset($charset = 'UTF-8')
    {
        static::$encoding = $charset;
    }

    /**
     * @return type
     */
    public static function getCharset()
    {
        return static::$encoding;
    }

    /**
     * @return type
     */
    public static function User()
    {
        $authenticator = new \Auth();

        return $authenticator->getUser();
    }
}
