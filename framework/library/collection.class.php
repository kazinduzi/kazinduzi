<?php
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
class Collection implements Countable, ArrayAccess, IteratorAggregate
{
    public $objectArray = [];

    public function doSomething()
    {
        echo "I'm doing something";
    }

    //**these are the required iterator functions
    public function offsetExists($offset)
    {
        if (isset($this->objectArray[$offset])) {
            return true;
        } else {
            return false;
        }
    }

    public function &offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->objectArray[$offset];
        } else {
            return false;
        }
    }

    public function offsetSet($offset, $value)
    {
        if ($offset) {
            $this->objectArray[$offset] = $value;
        } else {
            $this->objectArray[] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->objectArray[$offset]);
    }

    public function count()
    {
        return count($this->objectArray);
    }

    public function &getIterator()
    {
        return new ArrayIterator($this->objectArray);
    }
}
