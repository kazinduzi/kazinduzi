<?php

defined('KAZINDUZI_PATH') or die('No direct access script allowed');

/**
 * Kazinduzi Framework (http://framework.kazinduzi.com/)
 *
 * @author    Emmanuel Ndayiragije <endayiragije@gmail.com>
 * @link      http://kazinduzi.com
 * @copyright Copyright (c) 2010-2013 Kazinduzi. (http://www.kazinduzi.com)
 * @license   http://kazinduzi.com/page/license MIT License
 * @package   Kazinduzi
 */
class URL
{

    public static function title($str)
    {
	return self::toAscii($str);
    }

    public static function toAscii($str, $replace = array())
    {
	if (!empty($replace)) {
	    $str = str_replace((array) $replace, ' ', $str);
	}
	if (function_exists('iconv')) {
	    $str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
	} else {
	    $str = self::normalize($str);
	}
	$str = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $str);
	return $str;
    }

    public static function slugify($str, $replace = array(), $delimiter = '-', $maxLength = 200)
    {
	if (!empty($replace)) {
	    $str = str_replace((array) $replace, ' ', $str);
	}
	$str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
	$str = preg_replace("%[^-/+|\w ]%", '', $str);
	$str = strtolower(substr($str, 0, $maxLength));
	$str = preg_replace("/[\/_|+ -]+/", $delimiter, $str);
	return trim($str, '-');
    }

    public static function normalize($str)
    {
	$table = array(
	    'Š' => 'S', 'š' => 's', 'Đ' => 'Dj', 'đ' => 'dj', 'Ž' => 'Z', 'ž' => 'z', 'Č' => 'C', 'č' => 'c', 'Ć' => 'C', 'ć' => 'c',
	    'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
	    'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O',
	    'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss',
	    'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e',
	    'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o',
	    'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'ý' => 'y', 'þ' => 'b',
	    'ÿ' => 'y', 'Ŕ' => 'R', 'ŕ' => 'r',
	);
	return strtr($str, $table);
    }

}
