<?php

defined('KAZINDUZI_PATH') or die('No direct access script allowed');

/**
 * Description of url.
 *
 * @author Emmanuel_Leonie
 */
class String
{
    /**
     * Stringify the title.
     *
     * @param string $str
     *
     * @return string
     */
    public static function title($str)
    {
        return self::toAscii($str);
    }

    /**
     * String to ASCii.
     *
     * @param string $str
     * @param array  $replace
     *
     * @return string
     */
    public static function toAscii($str, $replace = [])
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

    /**
     * Returns a part of UTF-8 string.
     *
     * @param string
     * @param int
     * @param int
     *
     * @return string
     */
    public static function substr($s, $start, $length = null)
    {
        if ($length === null) {
            $length = self::length($s);
        }

        return function_exists('mb_substr') ? mb_substr($s, $start, $length, 'UTF-8') : iconv_substr($s, $start, $length, 'UTF-8'); // MB is much faster
    }

    /**
     * Slugify the string.
     *
     * @param string $str
     * @param array  $replace
     * @param string $delimiter
     * @param int    $maxLength
     *
     * @return string
     */
    public static function slugify($str, $replace = [], $delimiter = '-', $maxLength = 200)
    {
        if (!empty($replace)) {
            $str = str_replace((array) $replace, ' ', $str);
        }
        $str = self::toAscii($str);
        $str = preg_replace("%[^-/+|\w ]%", '', $str);
        $str = strtolower(substr($str, 0, $maxLength));
        $str = preg_replace("/[\/_|+ -]+/", $delimiter, $str);

        return trim($str, '-');
    }

    /**
     * Normalize the string.
     *
     * @param string $str
     *
     * @return string
     */
    public static function normalize($str)
    {
        $table = [
        'Š' => 'S', 'š' => 's', 'Đ' => 'Dj', 'đ' => 'dj', 'Ž' => 'Z', 'ž' => 'z', 'Č' => 'C', 'č' => 'c', 'Ć' => 'C', 'ć' => 'c',
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
        'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O',
        'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e',
        'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o',
        'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'ý' => 'y', 'þ' => 'b',
        'ÿ' => 'y', 'Ŕ' => 'R', 'ŕ' => 'r',
    ];

        return strtr($str, $table);
    }

    /**
     * Alternative normalizing the string.
     *
     * @param string $str
     *
     * @return string
     */
    public static function _normalize_($str)
    {
        // standardize line endings to unix-like
    $str = str_replace("\r\n", "\n", $str); // DOS
    $str = strtr($str, "\r", "\n"); // Mac
    // remove control characters; leave \t + \n
    $str = preg_replace('#[\x00-\x08\x0B-\x1F\x7F]+#', '', $str);
    // right trim
    $str = preg_replace("#[\t ]+$#m", '', $str);
    // leading and trailing blank lines
    $str = trim($str, "\n");

        return $str;
    }

    /**
     * Does haystack contain $needle?
     *
     * @param  string
     * @param  string
     *
     * @return bool
     */
    public static function contains($haystack, $needle)
    {
        return strpos($haystack, $needle) !== false;
    }

    /**
     * Returns UTF-8 string length.
     *
     * @param  string
     *
     * @return int
     */
    public static function length($str)
    {
        if ('UTF-8' == strtoupper(Kazinduzi::$encoding)) {
            return mb_strlen($str, Kazinduzi::$encoding);
        }

        return strlen(utf8_decode($str)); // fastest way
    }

    /**
     * Reverse string.
     *
     * @param  string  UTF-8 encoding
     *
     * @return string
     */
    public static function reverse($s)
    {
        return @iconv('UTF-32LE', 'UTF-8', strrev(@iconv('UTF-8', 'UTF-32BE', $s)));
    }

    /**
     * Capitalize string.
     *
     * @param  string  UTF-8 encoding
     *
     * @return string
     */
    public static function capitalize($string)
    {
        return mb_convert_case($string, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Generate random string.
     *
     * @param  int
     * @param  string
     *
     * @return string
     */
    public static function random($length = 10, $special_chars = true, $extra_special_chars = false)
    {
        $seed = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        if ($special_chars) {
            $seed .= '!@#$%^&*()';
        }
        if ($extra_special_chars) {
            $seed .= '-_ []{}<>~`+=,.;:/?|';
        }
        $seedLen = strlen($seed);
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            if ($i % 5 === 0) {
                $rand = lcg_value();
                $rand2 = microtime(true);
            }
            $rand *= $seedLen;
            $str .= $seed[($rand + $rand2) % $seedLen];
            $rand -= (int) $rand;
        }

        return $str;
    }

    /**
     * Check UTF-8.
     *
     * @param string $string
     *
     * @return bool
     */
    public static function isUtf8($string)
    {
        if (function_exists('mb_check_encoding') && is_callable('mb_check_encoding')) {
            return mb_check_encoding($string, 'UTF8');
        }

        return preg_match('%^(?:
              [\x09\x0A\x0D\x20-\x7E]            # ASCII
            | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
            |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
            | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
            |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
            |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
            | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
            |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
        )*$%xs', $string);
    }

    /**
     * @param string $dangerous_filename
     * @param string $platform
     *
     * @return string
     */
    public static function sanitizeFilename($dangerous_filename, $platform = 'Unix')
    {
        if (in_array(strtolower($platform), ['unix', 'linux'])) {
            $dangerous_characters = [' ', '"', "'", '&', '/', '\\', '?', '#'];
        } else {
            return $dangerous_filename;
        }

        return str_replace($dangerous_characters, '_', $dangerous_filename);
    }

    /**
     * Truncate a string.
     *
     * @param string $string
     * @param int    $limit
     * @param string $break
     * @param string $pad
     *
     * @return string
     */
    public static function truncate($string, $limit, $break = '.', $pad = '...')
    {
        if (strlen($string) <= $limit) {
            return $string;
        }
    // is $break present between $limit and the end of the string?
    if (false !== $breakpoint = strpos($string, $break, $limit)) {
        if ($breakpoint < strlen($string) - 1) {
            $string = substr($string, 0, $breakpoint).$pad;
        }
    }

        return $string;
    }

    /**
     * Compare two strings to avoid timing attacks.
     *
     * C function memcmp() internally used by PHP, exits as soon as a difference
     * is found in the two buffers. That makes possible of leaking
     * timing information useful to an attacker attempting to iteratively guess
     * the unknown string (e.g. password).
     *
     * @param string $expected
     * @param string $actual
     *
     * @return bool
     */
    public static function compareStrings($expected, $actual)
    {
        // Prevent issues if string length is 0
    $expected .= chr(0);
        $actual .= chr(0);
        $lenExpected = strlen($expected);
        $lenActual = strlen($actual);
    // Set the result to the difference between the lengths
    $result = $lenExpected - $lenActual;
    // Note that we ALWAYS iterate over the user-supplied length
    // This is to prevent leaking length information
    for ($i = 0; $i < $lenActual; $i++) {
        // Using % here is a trick to prevent notices
        // It's safe, since if the lengths are different
        // $result is already non-0
        $result |= (ord($expected[$i % $lenExpected]) ^ ord($actual[$i]));
    }

    // They are only identical strings if $result is exactly 0...
    return $result === 0;
    }
}
