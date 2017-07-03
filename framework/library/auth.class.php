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
class Auth
{
    const ADMIN_USER_LEVEL = 9;
    const NORMAL_USER_LEVEL = 1;
    const SESSION_EXPIRATION_TTL = 3600;

    /**
     * @var type
     */
    public $session_expire = self::SESSION_EXPIRATION_TTL;
    protected $session;

    /**
     * @var type
     */
    protected $username;

    /**
     * @var type
     */
    protected $password;

    /**
     * @var type
     */
    protected $remember;

    /**
     * @var type
     */
    private $logged_in;

    /**
     * @var type
     */
    private $remCookieUsr = 'usr';    // Cookie name for Username

    /**
     * @var type
     */
    private $remCookiePass = 'pass';  // Cookie name for password

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->session = Kazinduzi::session();
    }

    /**
     * @return type
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param type $access
     * @param type $default_redirect
     * @param type $redirect
     *
     * @return bool
     */
    public function check_access($access, $default_redirect = false, $redirect = false)
    {
        $user = isset($_SESSION['user']) ? $_SESSION['user'] : false;
        $qry = "SELECT * FROM `users` WHERE `id` = '".(int) $user['id']."';";
        $result = Kazinduzi::db()->setQuery($qry)->fetchAssocRow();
        if (empty($result)) {
            $this->logout();

            return false;
        }
        if ($access) {
            if ($access == $result['username']) {
                return true;
            } else {
                if ($redirect) {
                    redirect($redirect);
                } elseif ($default_redirect) {
                    redirect('/admin/');
                } else {
                    return false;
                }
            }
        }
    }

    /**
     * Get the logged in user.
     *
     * @return null|\User
     */
    public function getUser()
    {
        if (!isset($_SESSION['user']['id'])) {
            return;
        }

        return new \User($_SESSION['user']['id']);
    }

    /**
     * @param type $redirect
     * @param type $default_redirect
     *
     * @return bool
     */
    public function is_logged_in($redirect = false, $default_redirect = true)
    {
        $user = array_key_exists('user', $_SESSION) ? $_SESSION['user'] : false;
        if (!$user) {
            if ($redirect) {
                $_SESSION['redirect'] = $redirect;
            }
            if ($default_redirect) {
                redirect('/login');
            }

            return false;
        } else {
            // check if the session is expired if not reset the timer
        if ($user['expire'] && $user['expire'] < time()) {
            $this->logout();
            if ($redirect) {
                $_SESSION['redirect'] = $redirect;
            }
            if ($default_redirect) {
                redirect('/login');
            }

            return false;
        } else {
            // update the session expiration to last more time if they are not remembered
        if ($user['expire']) {
            $user['expire'] = time() + $this->session_expire;
            $_SESSION['user'] = $user;
        }
        }

            return true;
        }
    }

    /**
     * @return type
     */
    public static function is_authenticated()
    {
        return isset($_SESSION['user']['logged']) ? $_SESSION['user']['logged'] : false;
    }

    /**
     * @return type
     */
    public static function is_admin()
    {
        return isset($_SESSION['user']['level']) and ($_SESSION['user']['level'] >= self::ADMIN_USER_LEVEL) ? true : false;
    }

    /**
     * Is the user administrator.
     *
     * @return bool
     */
    public function isAdmin()
    {
        if ($this->is_logged_in()) {
            if (isset($_SESSION['user']['level']) && $_SESSION['user']['level'] >= self::ADMIN_USER_LEVEL) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param type $options
     *
     * @return bool
     */
    public function login($options = [])
    {
        foreach ($options as $key => $value) {
            if (strtolower($key) == 'username') {
                $this->username = $value;
            }
            if (strtolower($key) == 'password') {
                $this->password = $value;
            }
            if (strtolower($key) == 'remember') {
                $this->remember = $value;
            }
        }
        if (empty($this->username) || empty($this->passeword)) {
            $this->getCookieUser();
        }
        if (false !== $user = $this->authenticate()) {
            if ($user instanceof User) {
                $_SESSION['user'] = (array) $user->values;
                $_SESSION['user']['logged'] = $this->logged_in = true;
            }
            if (!$this->remember) {
                $_SESSION['user']['expire'] = time() + $this->session_expire;
            } else {
                setcookie($this->remCookieUsr, base64_encode($this->username), time() + 10000, '/');
                setcookie($this->remCookiePass, base64_encode($this->password), time() + 10000, '/');
                $_SESSION['user']['expire'] = false;
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    private function authenticate()
    {
        $this->user = User::getInstance()->getUserByUsername($this->username);
        if (empty($this->user)) {
            return false;
        }
        $passwordHashed = Security::bcryptPassword($this->password);
        if (\Security::compareStrings($this->user->password, $passwordHashed)) {
            return $this->user;
        } else {
            return false;
        }
    }

    /**
     * @return null
     */
    private function getCookieUser()
    {
        if (isset($_COOKIE[$this->remCookieUsr]) && isset($_COOKIE[$this->remCookiePass])) {
            $this->username = base64_decode($_COOKIE[$this->remCookieUsr]);
            $this->password = base64_decode($_COOKIE[$this->remCookiePass]);
        }
    }


    public function logout()
    {
        session_unset();
        setcookie($this->remCookieUsr, '', time() - self::SESSION_EXPIRATION_TTL, '/');
        setcookie($this->remCookiePass, '', time() - self::SESSION_EXPIRATION_TTL, '/');
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - self::SESSION_EXPIRATION_TTL, '/');
        }
        $_SESSION = [];
        session_destroy();
    }

    /**
     * @return type
     */
    public function __toString()
    {
        return get_class();
    }
}
