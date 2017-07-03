<?php

defined('KAZINDUZI_PATH') || exit('No direct script access allowed');

use Kazinduzi\Core\Kazinduzi;

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
class Security
{
    /**
     * @var string keyname used for token storage
     */
    public static $token_name = 'security_token';

    /**
     * @var int expiration time for the security token
     */
    public static $token_expiration = 7200;

    /**
     * Generate and store a unique token which can be used to help prevent
     * $token = Security::token();
     * You can insert this token into your forms as a hidden field.
     * This provides a basic, but effective, method of preventing CSRF attacks.	 *.
     *
     * @param   bool  force a new token to be generated?
     *
     * @return string
     *
     * @uses    Session::instance
     */
    public static function token($new = false)
    {
        $Session = Kazinduzi::session();
    // Get the current token
    $token = $Session->get(self::$token_name);
        $isExpired = (time() - (int) $Session->get('token_expiration_time')) > 0 ? true : false;
        if ($new === true || !$token || $isExpired) {
            // Generate a new unique token
        $token = self::generateToken();
        // Store the new token
        $Session->set(self::$token_name, $token);
        // Store the expiration
        $Session->set('token_expiration_time', time() + self::$token_expiration);
        }

        return $token;
    }

    /**
     * Generate the secure random string.
     *
     * @return string
     */
    protected static function generateToken()
    {
        if (version_compare(PHP_VERSION, '5.3.4', '>=') && function_exists('openssl_random_pseudo_bytes')) {
            return base64_encode(openssl_random_pseudo_bytes(32));
        } else {
            return sha1(uniqid(mt_rand(), true));
        }
    }

    /**
     * Check if the given token matches the currently stored security token.
     *
     * @param   string   token to check
     *
     * @return bool
     *
     * @uses    Security::token
     */
    public static function check($token)
    {
        $session = Kazinduzi::session();
        if (true === static::compareStrings($session->get(static::$token_name), $token)) {
            $session->remove(static::$token_name);

            return true;
        }

        return false;
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

    /**
     * Remove image tags from a string.
     *
     * @param   string  string to sanitize
     *
     * @return string
     */
    public static function removeImageTags($str)
    {
        return preg_replace('/<img[^>]+\>/is', '$1', $str);
    }

    /**
     * Encodes PHP tags in a string.
     *
     * @param   string  string to sanitize
     *
     * @return string
     */
    public static function encodePHPTags($str)
    {
        return str_replace(['<?', '?>'], ['&lt;?', '?&gt;'], $str);
    }

    /**
     * BCrypt hashing pas.
     *
     * @param string $password
     *
     * @return string
     */
    public static function bcryptPassword($password)
    {
        $Crypt = new Bcrypt();
        $Crypt->setSalt('MyKazinduziIsGreat');

        return $Crypt->hash($password);
    }
}
