<?php

defined('KAZINDUZI_PATH') || exit('No direct script access allowed');
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
abstract class Observable
{
    /**
     * Array of Observers.
     *
     * @var array
     */
    private $observers = [];

    public function __construct()
    {
        $this->observers = [];
    }

    /**
     * Calls the update() function using the reference to each
     * registered observer, passing an optional argument for the
     * event - used by children of Observable.
     *
     * @return void
     */
    public function notifyAll($arg = null)
    {
        foreach (array_keys($this->observers) as $key) {
            $this->observers[$key]->update($this, $arg);
        }
    }

    /**
     * Attaches an observer to the observable.
     *
     * @return void
     */
    public function addObserver($observer)
    {
        $this->observers[] = $observer;
    }
}
