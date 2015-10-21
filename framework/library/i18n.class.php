<?php

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
class I18n
{

    protected $lang;
    protected $source = 'en_US';
    protected static $loaded = array();

    /**
     *
     * @param type $lang
     * @return \I18n
     */
    public function setLanguage($lang = null)
    {
	if ($lang) {
	    $this->lang = strtolower(str_replace(array(' ', '-'), '_', $lang));
	}
	return $this;
    }

    /**
     *
     * @return type
     */
    public function getLanguage()
    {
	$config = Kazinduzi::config('main')->toArray();
	return $this->lang ? $this->lang : $config['lang'];
    }

    /**
     *
     * @param type $string
     * @param type $lang
     * @return type
     */
    public function translate($string, $lang = null)
    {
	if ($lang) {
	    $this->setLanguage($lang);
	}
	if (strpos($string, '.') !== false) {
	    $segments = explode('.', $string);
	    $table = $this->loadFile($segments[0], $this->getLanguage());
	    $key = $segments[1];
	} else {
	    $table = $this->load($this->getLanguage());
	    $key = $string;
	}
	return isset($table[$key]) ? $table[$key] : $key;
    }

    /**
     *
     * @param type $lang
     */
    private function load($lang)
    {
	if (isset(static::$loaded[$lang])) {
	    return static::$loaded[$lang];
	}
	if (!is_file($language_file = APP_PATH . '/i18n/' . $lang . '.php')) {
	    return;
	}
	$table = include $language_file;
	return static::$loaded[$lang] = $table;
    }

    /**
     * 
     * @param string $langFile
     * @return array
     */
    private function loadFile($langFile, $locale = null)
    {
	if (isset(static::$loaded[$langFile])) {
	    return static::$loaded[$langFile];
	}
	$locale = $locale ? : $this->getLanguage();
	if (!is_file($languageFilePath = APP_PATH . '/i18n/' . $locale . '/' . $langFile . '.php')) {
	    return;
	}
	$table = include $languageFilePath;
	return static::$loaded[$langFile] = $table;
    }

}
