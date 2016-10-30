<?php

defined('KAZINDUZI_PATH') || exit('No direct script access allowed');
/*
 * Kazinduzi Framework (http://framework.kazinduzi.com/)
 *
 * @author    Emmanuel Ndayiragije <endayiragije@gmail.com>
 * @link      http://kazinduzi.com
 * @copyright Copyright (c) 2010-2013 Kazinduzi. (http://www.kazinduzi.com)
 * @license   http://kazinduzi.com/page/license MIT License
 * @package   Kazinduzi
 */
sanitize_input();
Kazinduzi::init();

use Kazinduzi\IoC\Container;

$container = new Container();
$container['db'] = function () {
    return Kazinduzi::db();
};
$container['session'] = function () {
    return Kazinduzi::session();
};
$container['cache'] = function () {
    return Kazinduzi::cache();
};

$dispatcher = new \Kazinduzi\Core\Dispatcher($container);
$dispatcher->dispatch();
