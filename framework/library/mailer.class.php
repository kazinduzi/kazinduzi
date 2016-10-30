<?php

defined('KAZINDUZI_PATH') || exit('No direct script access allowed');

require_once 'phpmailer/class.phpmailer.php';

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
class Mailer extends PHPMailer
{
    public static function getInstance($id = 'Kazinduzi')
    {
        static $instances;
        if (!isset($instances)) {
            $instances = [];
        }
        if (empty($instances[$id])) {
            $instances[$id] = new static();
        }

        return $instances[$id];
    }
}
