<?php defined('KAZINDUZI_PATH') or die('No direct access script allowed');
/**
 * Kazinduzi Framework (http://framework.kazinduzi.com/)
 *
 * @author    Emmanuel Ndayiragije <endayiragije@gmail.com>
 * @link      http://kazinduzi.com
 * @copyright Copyright (c) 2010-2013 Kazinduzi. (http://www.kazinduzi.com)
 * @license   http://kazinduzi.com/page/license MIT License
 * @package   Kazinduzi
 */
/**
 * Description of Session_default
 *
 * @author Emmanuel_Leonie
 */
final class SessionDefault extends Session {

    public function __construct($configs = null) {
        $configs = !isset($configs) ? self::$configs : $configs;
        if (!$this->ua){
            $this->ua = \Request::getInstance()->user_agent();
        }
        if (!$this->ip){
            $this->ip = \Request::getInstance()->ip_address();
        }
    }
}