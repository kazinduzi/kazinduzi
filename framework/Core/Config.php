<?php
namespace Kazinduzi\Core;

defined('KAZINDUZI_PATH') || exit('No direct script access allowed');
/**
 * Kazinduzi Framework (http://framework.kazinduzi.com/)
 *
 * @author    Emmanuel Ndayiragije <endayiragije@gmail.com>
 * @link      http://kazinduzi.com
 * @copyright Copyright (c) 2010-2013 Kazinduzi. (http://www.kazinduzi.com)
 * @license   http://kazinduzi.com/page/license MIT License
 * @package   Kazinduzi
 */

use ArrayAccess;

final class Config implements ArrayAccess 
{

    /**
     * @var $config variable to hold the configuration data
     */
    private $config;

    /**
     * @var $paths folders where the configs are stored
     */
    protected $paths = array(APP_PATH, KAZINDUZI_PATH);

    /**
     * @staticvar For multiple instances of Config class
     */
    protected static $instances = array();

    /**
     * @staticvar For one configuration file to be loaded
     */
    protected static $instance = array();

    /**
     * Constructor
     * 
     * @param string $group
     */
    public function __construct($group = null) 
    {
        if ($group === null) {
            $group = 'main';
        }
        $this->load($group);
    }


    /**
     * Return the current group in serialized form.     
     *
     * @return  string
     */
    public function __toString() 
    {
        return serialize($this->config);
    }

    public function load($filename) 
    {
        foreach ($this->paths as $path) {
            if (is_file($file = $path . DS . 'configs' . DS . $filename . EXT)) {		
                require $file;
                $this->config = &$config;		
                unset ($config);
            }
        }
    }

    public function as_array() 
    {
        return (array)$this->config;
    }

    public function toArray() 
    {
        return $this->as_array();
    }

    /**
     * Get a variable from the configuration or return the default value.
     *
     *  $value = $config->get($key);
     *
     * @param   string   array key
     * @param   mixed    default value
     * @return  mixed
     */
    public function get($key, $default = null) 
    {
        return $this->offsetExists($key) ? $this->offsetGet($key) : $default;
    }

    /**
     * Sets a value in the configuration array.
     *
     *     $config->set($key, $new_value);
     *
     * @param   string   array key
     * @param   mixed    array value
     * @return  $this
     */
    public function set($key, $value) 
    {
        $this->offsetSet($key, $value);
        return $this;
    }

    /***
     *
     */
    public function offsetSet($offset, $value) 
    {
        if (is_null($offset)) {
            $this->config[] = $value;
        } else {
            $this->config[$offset] = $value;
        }
    }

    /**
     * 
     * @param mixed $offset
     * @return mixed
     */
    public function offsetExists($offset) 
    {
        return isset($this->config[$offset]);
    }

    /**
     * 
     * @param mixed $offset
     */
    public function offsetUnset($offset) 
    {
        unset($this->config[$offset]);
    }

    /**
     * 
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset) 
    {
        return isset($this->config[$offset]) ? $this->config[$offset] : null;
    }






    /**
     * -----------------------------------------------------------------------------------------------------
     * Loading only one configuration file
     * -----------------------------------------------------------------------------------------------------
     * @uses The param to find the config file and load it to be accessed
     * @param This param can be ('main'|'session'|'database') or any
     * @return Config Object for the specified param.
     */
    public static function instance() 
    {
        $groups = func_get_args();
        if ($groups === array()) {
            $groups = array('main');
        }
        foreach ($groups as $key => $group) {
            if (empty(self::$instance[$group])) {
                self::$instance[$group] =  new self($group);
            }
        }
        return self::$instance[$group];
    }


    /**
     * -----------------------------------------------------------------------------------------------------
     * Loading multiple configs files at once
     * -----------------------------------------------------------------------------------------------------
     *
     * Try to load multiple configuration files at once in an Array
     * @access the appropriated data using the appropriate filename without extension as key
     * @example:
     * $configs = Config::instances(array('foo','bar'))
     * foreach($configs as $key => $config)
     * {
     *     $configs[$key] = $config;
     * }
     * $configs is the array of Config Objects for foo and bar config files
     */
    public static function instances() 
    {
        $groups = func_get_args();
        if ($groups == array()) {
            $groups = array('main');
        }
        foreach ($groups as $key => $group) {
            if (is_array($group) AND !empty($group)) {
                foreach ($group as $key => $group) {
                    if (empty(self::$instances[$group])) {
                        self::$instances[$group] =  new self($group);
                    }
                }
            }
            if (empty(self::$instances[$group])) {
                self::$instances[$group] =  new self($group);
            }
        }
        return self::$instances;
    }

}