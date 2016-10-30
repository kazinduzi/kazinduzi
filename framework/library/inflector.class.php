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
class Inflector
{
    // Cached inflections
    protected static $cache = [];

    protected static $irregular = [];

    /**
     * Makes a plural word singular.
     *
     *     echo Inflector::singular('cats'); // "cat"
     *     echo Inflector::singular('fish'); // "fish"
     *
     * You can also provide the count to make inflection more intelligent.
     * In this case, it will only return the singular value if the count is
     * greater than one and not zero.
     *
     *     echo Inflector::singular('cats', 2); // "cats"
     *
     * [!!] Special inflections are defined in `config/inflector.php`.
     *
     * @param   string   word to singularize
     * @param   int  count of thing
     *
     * @return string
     */
    public static function singular($str, $count = null)
    {
        // $count should always be a float
        $count = ($count === null) ? 1.0 : (float) $count;

        // Do nothing when $count is not 1
        if ($count != 1) {
            return $str;
        }

        // Remove garbage
        $str = strtolower(trim($str));

        // Cache key name
        $key = 'singular_'.$str.$count;

        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        if ($irregular = array_search($str, self::$irregular)) {
            $str = $irregular;
        } elseif (preg_match('/us$/', $str)) {
            // http://en.wikipedia.org/wiki/Plural_form_of_words_ending_in_-us
            // Already singular, do nothing
        } elseif (preg_match('/[sxz]es$/', $str) or preg_match('/[^aeioudgkprt]hes$/', $str)) {
            // Remove "es"
            $str = substr($str, 0, -2);
        } elseif (preg_match('/[^aeiou]ies$/', $str)) {
            // Replace "ies" with "y"
            $str = substr($str, 0, -3).'y';
        } elseif (substr($str, -1) === 's' and substr($str, -2) !== 'ss') {
            // Remove singular "s"
            $str = substr($str, 0, -1);
        }

        return self::$cache[$key] = $str;
    }

    /**
     * Makes a singular word plural.
     *
     *     echo Inflector::plural('fish'); // "fish"
     *     echo Inflector::plural('cat');  // "cats"
     *
     * You can also provide the count to make inflection more intelligent.
     * In this case, it will only return the plural value if the count is
     * not one.
     *
     *     echo Inflector::singular('cats', 3); // "cats"
     *
     * [!!] Special inflections are defined in `config/inflector.php`.
     *
     * @param   string   word to pluralize
     * @param   int  count of thing
     *
     * @return string
     */
    public static function plural($str, $count = null)
    {
        // $count should always be a float
        $count = ($count === null) ? 0.0 : (float) $count;

        // Do nothing with singular
        if ($count == 1) {
            return $str;
        }

        // Remove garbage
        $str = strtolower(trim($str));

        // Cache key name
        $key = 'plural_'.$str.$count;

        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }


        if (isset(self::$irregular[$str])) {
            $str = self::$irregular[$str];
        } elseif (preg_match('/[sxz]$/', $str) or preg_match('/[^aeioudgkprt]h$/', $str)) {
            $str .= 'es';
        } elseif (preg_match('/[^aeiou]y$/', $str)) {
            // Change "y" to "ies"
            $str = substr_replace($str, 'ies', -1);
        } else {
            $str .= 's';
        }
        // Set the cache and return
        return self::$cache[$key] = $str;
    }

    /**
     * Returns given word as CamelCased.
     *
     * Converts a word like "send_email" to "SendEmail". It
     * will remove non alphanumeric character from the word, so
     * "who's online" will be converted to "WhoSOnline"
     */
    public static function camelize($str)
    {
        // Cache key name
        $key = 'camelize_'.$str;
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }
        $str = str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9]+/', ' ', $str)));
        // Set the cache and return
        return self::$cache[$key] = $str;
    }

    /**
     * Converts a word "into_it_s_underscored_version".
     *
     * Convert any "CamelCased" or "ordinary Word" into an
     * "underscored_word".
     *
     * This can be really useful for creating friendly URLs.
     */
    public static function underscore($str)
    {
        $key = 'underscore_'.$str;
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }
        $str = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $class_name));

        return self::$cache[$key] = $str;
    }

    /**
     * @param type $str
     *
     * @return type
     */
    public static function pathize($str)
    {
        $key = 'pathize_'.$str;
        $str = strtolower(preg_replace('/([a-z])([A-Z])/', '$1/$2', $str));

        return self::$cache[$key] = $str;
    }

    /**
     * Returns a human-readable string from $str.
     *
     * Returns a human-readable string from $str, by replacing
     * underscores with a space, and by upper-casing the initial
     * character by default.
     *
     * If you need to uppercase all the words you just have to
     * pass 'all' as a second parameter.
     */
    public static function humanize($str, $uppercase = '')
    {
        $key = 'humanize_'.$str;
        $uppercase = $uppercase == 'all' ? 'ucwords' : 'ucfirst';
        $str = $uppercase(str_replace('_', ' ', preg_replace('/_id$/', '', $str)));

        return self::$cache[$key] = $str;
    }
}
