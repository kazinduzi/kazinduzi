<?php defined('KAZINDUZI_PATH') || exit('No direct script access allowed');
/**
 * Kazinduzi Framework (http://framework.kazinduzi.com/)
 *
 * @author    Emmanuel Ndayiragije <endayiragije@gmail.com>
 * @link      http://kazinduzi.com
 * @copyright Copyright (c) 2010-2013 Kazinduzi. (http://www.kazinduzi.com)
 * @license   http://kazinduzi.com/page/license MIT License
 * @package   Kazinduzi
 */

class Registry 
{
    /**
     * @the vars array
     * @access private
     */
     private $vars = array();
     
    /**
     *
     * @var type
     */
     private static $instance = null;

    /**
     * @set undefined vars
     * @param string $index
     * @param mixed $value
     * @return void
     */
    public function __set($index, $value) 
    {
        $this->vars[$index] = $value;
    }

    /**
     * @get variables
     * @param mixed $index
     * @return mixed
     */
    public function __get($index) 
    {
        if (array_key_exists($index, $this->vars)) {
            return $this->vars[$index];
        }
        return null;
    }

    /**
     *
     * @param type $name
     * @return type
     */
    public function  __isset($name) 
    {
        return array_key_exists($name, $this->vars);
    }

    /**
     *
     * @param type $name
     */
     public function  __unset($name) {
        if (array_key_exists($name, $this->vars)) {
            unset($this->vars[$name]);
        }
     }

    /**
     *
     * @return type
     */
    private function  __construct() 
    {
         
    }

    /**
     *
     * @return type
     */
    private function  __clone() 
    {
        
    }

    /**
     * method to stringfy the Registry class
     * @access public
     */
    public function toString() 
    {
        return $this->__toString();
    }

    /**
     *
     * @return type
     */
    public function __toString() 
    {
        return (array) $this->vars;
    }

    /**
     *
     * @return type
     */
    public static function getInstance() 
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

}
