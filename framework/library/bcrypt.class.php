<?php defined('KAZINDUZI_PATH') || exit('No direct script access allowed');
/**
 * Kazinduzi Framework (http://framework.kazinduzi.com/)
 *
 * @author    Emmanuel Ndayiragije <endayiragije@gmail.com>
 * @link      http://kazinduzi.com
 * @copyright Copyright (c) 2010-2013 Kazinduzi. (http://www.kazinduzi.com)
 * @license   http://kazinduzi.com/page/license MIT License
 * @package   Kazinduzi
 */

class Bcrypt {
    const MIN_SALT_SIZE = 16;

    /**
     * @var string
     */
    protected $rounds;

    /**
     * @var string
     */
    protected $salt;

    /**
     *
     * @param integer $rounds
     */
    public function __construct($rounds = '14') {
        $this->rounds = $rounds;
    }

    /**
     * Bcrypt
     *
     * @param  string $password
     * @throws Exception\RuntimeException
     * @return string
     */
    public function hash($password){
        if (empty($this->salt)) {
            $this->salt = uniqid(mt_rand(), true);
        }
        $salt64 = substr(str_replace('+', '.', base64_encode($this->salt)), 0, 22);
        /**
         * Check for security flaw in the bcrypt implementation used by crypt()
         * @see http://php.net/security/crypt_blowfish.php
         */
        if (version_compare(PHP_VERSION, '5.3.7') >= 0){
            $prefix = '$2y$';
        } else {
            $prefix = '$2a$';
            // check if the password contains 8-bit character
            if (preg_match('/[\x80-\xFF]/', $password)){
                throw new RuntimeException(
                    'The bcrypt implementation used by PHP can contains a security flaw ' .
                    'using password with 8-bit character. ' .
                    'We suggest to upgrade to PHP 5.3.7+ or use passwords with only 7-bit characters'
                );
            }
        }
        $hash = crypt($password, $prefix . $this->rounds . '$' . $salt64);
        if (strlen($hash) <= 13){
            throw new RuntimeException('Error during the bcrypt generation');
        }
        return $hash;
    }

    /**
     * Verify if a password is correct against an hash value
     *
     * @param  string $password
     * @param  string $hash
     * @return boolean
     */
    public function verify($password, $hash){
        return ($hash === crypt($password, $hash));
    }

    /**
     * Set the rounds parameter
     *
     * @param  integer|string $rounds
     * @throws Exception\InvalidArgumentException
     * @return Bcrypt
     */
    public function setRounds($rounds){
        if (!empty($rounds)){
            $rounds = (int) $rounds;
            if ($rounds < 4 || $rounds > 31){
                throw new InvalidArgumentException(
                    'The rounds parameter of bcrypt must be in range 04-31'
                );
            }
            $this->rounds = sprintf('%1$02d', $rounds);
        }
        return $this;
    }

    /**
     * Get the rounds parameter
     *
     * @return string
     */
    public function getRounds(){
        return $this->rounds;
    }

    /**
     * Set the salt value
     *
     * @param  string $salt
     * @throws InvalidArgumentException
     * @return Bcrypt
     */
    public function setSalt($salt){
        if (strlen($salt) < self::MIN_SALT_SIZE){
            throw new InvalidArgumentException(
                'The length of the salt must be at lest ' . self::MIN_SALT_SIZE . ' bytes'
            );
        }
        $this->salt = $salt;
        return $this;
    }

    /**
     * Get the salt value
     *
     * @return string
     */
    public function getSalt(){
        return $this->salt;
    }
}