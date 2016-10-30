<?php

defined('KAZINDUZI_PATH') or die('No direct access script allowed');
/**
 * Kazinduzi Framework (http://framework.kazinduzi.com/).
 *
 * @author    Emmanuel Ndayiragije <endayiragije@gmail.com>
 *
 * @link      http://kazinduzi.com
 *
 * @copyright Copyright (c) 2010-2013 Kazinduzi. (http://www.kazinduzi.com)
 * @license   http://kazinduzi.com/page/license MIT License
 */
/**
 * Description of session_iterator.
 *
 * @author Emmanuel_Leonie
 */
final class SessionIterator implements Iterator
{
    /**
     * @var array list of keys in the map
     */
    private $keys;
    /**
     * @var mixed current key
     */
    private $key;

    /**
     * @var array
     */
    private $data;

    /**
     * Constructor.
     *
     * @param array the data to be iterated through
     */
    public function __construct(array $data)
    {
        if (empty($data)) {
            $this->data = &$_SESSION;
        } else {
            $this->data = $data;
        }
        $this->keys = array_keys($data);
        //$this->keys = array_keys($_SESSION);
        //
        // If the client 'User-Agent' is not set from the DB session, we fetch the new one from the client request
        if (!$this->ua) {
            $this->ua = Request::getInstance()->user_agent();
        }
        // If the client IP-Address is not set from the DB session, we fetch the new one from the client request
        if (!$this->ip) {
            $this->ip = Request::getInstance()->ip_address();
        }
    }

    /**
     * Rewinds internal array pointer.
     * This method is required by the interface Iterator.
     */
    public function rewind()
    {
        $this->key = reset($this->keys);
    }

    /**
     * Returns the key of the current array element.
     * This method is required by the interface Iterator.
     *
     * @return mixed the key of the current array element
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * Returns the current array element.
     * This method is required by the interface Iterator.
     *
     * @return mixed the current array element
     */
    public function current()
    {
        return isset($this->data[$this->key]) ? $this->data[$this->key] : null;
        //return isset($_SESSION[$this->key]) ? $_SESSION[$this->key] : null;
    }

    /**
     * Moves the internal pointer to the next array element.
     * This method is required by the interface Iterator.
     */
    public function next()
    {
        do {
            $this->key = next($this->keys);
        } while (!isset($this->data[$this->key]) && $this->key !== false);
        //while(!isset($_SESSION[$this->key]) && $this->key !== false);
    }

    /**
     * Returns whether there is an element at current position.
     * This method is required by the interface Iterator.
     *
     * @return bool
     */
    public function valid()
    {
        return $this->key !== false;
    }
}
