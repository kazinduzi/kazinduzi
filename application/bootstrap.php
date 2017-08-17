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

use Kazinduzi\IoC\Container;
use Kazinduzi\Core\Kazinduzi;
use Kazinduzi\Core\Dispatcher;
use Kazinduzi\Templating\TwigEngine;
use Kazinduzi\Core\Template;
use Kazinduzi\Core\Request;
use Kazinduzi\Core\Response;

sanitize_input();
Kazinduzi::init();

$container = new Container();
$container['db'] = function() {
    return Kazinduzi::db();
};

$container['session'] = function() {
    return Kazinduzi::session();
};

$container['request'] = function() {
    return Request::createFromGlobals();
};

$container['cache'] = function() {
    return Kazinduzi::cache();
};

$container['templating'] = function() {
    $twig = new TwigEngine(APP_PATH . '/views');
    $template = new Template();
    $template->setTemplatingEngine($twig);
    return $template;
};

$dispatcher = new Dispatcher($container);
$dispatcher->dispatch();
