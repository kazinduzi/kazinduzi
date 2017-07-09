<?php

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
}

final class Classloader
{
    /**
     * Register class.
     *
     * @param string $class
     */
    public function register($class)
    {
        $camelcasedClass = strtolower(preg_replace('/([a-z])([A-Z])/', '$1/$2', $class));
        $class = ltrim($class, '\\');
    // CORE
    if (is_file(strtolower(CORE_PATH.DIRECTORY_SEPARATOR.$class.CLASS_EXT))) {
        require strtolower(CORE_PATH.DIRECTORY_SEPARATOR.$class.CLASS_EXT);
    }
    // DB
    elseif (is_file(strtolower(DB_PATH.DIRECTORY_SEPARATOR.$class.EXT))) {
        require strtolower(DB_PATH.DIRECTORY_SEPARATOR.$class.EXT);
    }
    // CLASSES
    elseif (is_file(strtolower(APP_PATH.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.$camelcasedClass.CLASS_EXT))) {
        require strtolower(APP_PATH.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.$camelcasedClass.CLASS_EXT);
    }
    // HELPERS
    elseif (is_file(strtolower(KAZINDUZI_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.$camelcasedClass.CLASS_EXT))) {
        require strtolower(KAZINDUZI_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.$camelcasedClass.CLASS_EXT);
    }
    // LIBRARY
    elseif (is_file(strtolower(LIB_PATH.DIRECTORY_SEPARATOR.$camelcasedClass.CLASS_EXT))) {
        require strtolower(LIB_PATH.DIRECTORY_SEPARATOR.$camelcasedClass.CLASS_EXT);
    }
    // CONTROLLER
    elseif (is_file(CONTROLLERS_PATH.DIRECTORY_SEPARATOR.Inflector::pathize(str_replace('Controller', '', lcfirst($class))).'Controller'.EXT)) {
        require CONTROLLERS_PATH.DIRECTORY_SEPARATOR.Inflector::pathize(str_replace('Controller', '', lcfirst($class))).'Controller'.EXT;
    }

    // MODEL
    elseif (is_file(strtolower(MODELS_PATH.DIRECTORY_SEPARATOR.$camelcasedClass.MODEL_EXT))) {
        require strtolower(MODELS_PATH.DIRECTORY_SEPARATOR.$camelcasedClass.MODEL_EXT);
    }

    // MODULES
    elseif (is_file(strtolower(MODULES_PATH.DIRECTORY_SEPARATOR.DS.$camelcasedClass.EXT))) {
        require strtolower(MODULES_PATH.DIRECTORY_SEPARATOR.DS.$camelcasedClass.EXT);
    }
    }

    /**
     * @param string $class
     */
    public function loadLowercase($class)
    {
        $camelcasedClass = strtolower(preg_replace('/([a-z])([A-Z])/', '$1/$2', $class));
        $class = ltrim($class, '\\');
        $fileName = '';
        $namespace = '';
        if (false !== $pos = strrpos($class, '\\') /* && $pos !== false */) {
            $namespace = substr($class, 0, $pos);
            $class = substr($class, $pos + 1);
            $camelcaseClass = (preg_replace('/([a-z])([A-Z])/', '$1/$2', $class));
            $fileName = str_replace('\\', DIRECTORY_SEPARATOR, $namespace).DIRECTORY_SEPARATOR.str_replace('_', DIRECTORY_SEPARATOR, $camelcaseClass).'.php';
            include strtolower($fileName);
        }
    }

    /**
     * Autoloader for Modules.
     *
     * @param string $className
     *
     * @return void
     */
    public function psr0($className)
    {
        $className = ltrim($className, '\\');
        $fileName = '';
        $namespace = '';
        if (false !== $lastNsPos = strripos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName = str_replace('\\', DIRECTORY_SEPARATOR, $namespace).DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className).'.php';
        include $fileName;
    }
}

spl_autoload_register(null, false);
spl_autoload_extensions('.php');
spl_autoload_register([new Classloader(), 'register']);
spl_autoload_register([new Classloader(), 'psr0']);
ini_set('unserialize_callback_func', 'spl_autoload_call');


// Kazinduzi ClassLoader
require __DIR__.'/Autoload/src/Autoloader.php';
$autoloader = new \Kazinduzi\Autoload\Autoloader();
$autoloader->addPrefix('Kazinduzi\\Core', 'framework/Core');
$autoloader->addPrefix('Kazinduzi\\Db', 'framework/Db');
$autoloader->addPrefix('Kazinduzi\\Session', 'framework/Session/src');
$autoloader->addPrefix('Kazinduzi\\Cache', 'framework/Cache');
$autoloader->addPrefix('Kazinduzi\\IoC', 'framework/IoC/src');
$autoloader->register(true);
