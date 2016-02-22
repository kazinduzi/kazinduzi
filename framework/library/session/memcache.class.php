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
 * Memcache session storage handler for PHP
 *
 * @see http://www.php.net/manual/en/function.session-set-save-handler.php
 */
final class SessionMemcache extends Session
{
    /**
     *
     * @var type
     */
    protected  static $memcache;

    /**
     *
     * @var type
     */
    protected static $_flag;

    /**
     * Constructor
     *
     * @access public
     * @param array $options optional parameters
     */
    public function __construct(array $configs = null) {
        if (!(extension_loaded('memcache') && class_exists('Memcache'))) {
            throw new \Exception('Memcache PHP extention not loaded');
        }
        $configs = isset($configs) ? $configs : self::$configs;
        // Setup the flag
        if ($configs['compression']) {
            self::$_flag = MEMCACHE_COMPRESSED;
        } else {
            self::$_flag = 0;
        }
        
        // If the client 'User-Agent' is not set from the DB session, we fetch the new one from the client request
        if (!$this->ua) {
            $this->ua = Request::getInstance()->user_agent();
        }
        // If the client IP-Address is not set from the DB session, we fetch the new one from the client request
        if (!$this->ip) {
            $this->ip = Request::getInstance()->ip_address();
        }
    }

    /**
     * Tell we want use custom session storage
     * @return boolean
     */
    public function getUseCustomStorage() {
        return true;
    }

    /**
	 * Open the SessionHandler backend.
	 *
	 * @access public
	 * @param string $save_path	The path to the session object.
	 * @param string $session_name  The name of the session.
	 * @return boolean  True on success, false otherwise.
	 */
	public function openSession($save_path, $session_name) {
		self::$memcache = new Memcache;
		foreach (self::$configs['servers'] as $server) {
			if (!$server) {
                throw new Exception('No Memcache servers defined in configuration');
            }
            if (self::$configs['compatibility']) {
                // No status for compatibility mode (#ZF-5887)
                self::$memcache->addServer($server['host'], $server['port'], $server['persistent'], $server['weight'], $server['timeout'], $server['retry_interval']);
            } else {
                self::$memcache->addServer($server['host'], $server['port'], $server['persistent'], $server['weight'], $server['timeout'], $server['retry_interval'], $server['status'], $server['failure_callback']);
            }
		}
		return true;
	}

	/**
	 * Close the SessionHandler backend.
	 *
	 * @access public
	 * @return boolean  True on success, false otherwise.
	 */
	public function closeSession() {
		return self::$memcache->close();
	}

	/**
	 * Read the data for a particular session identifier from the
	 * SessionHandler backend.
	 *
	 * @access public
	 * @param string $id  The session identifier.
	 * @return string  The session data.
	 */
	public function readSession($id) {
		$id = 'sess_'.$id;
		$this->_setExpire($id);
		return self::$memcache->get($id);
	}

    /**
	 * Write session data to the SessionHandler backend.
	 *
	 * @access public
	 * @param string $id			The session identifier.
	 * @param string $data          The session data.
	 * @return boolean  true on success, false otherwise.
	 */
	public function writeSession($id, $data) {
		$id = 'sess_'.$id;
		if (self::$memcache->get($id.'_expire')) {
			self::$memcache->replace($id.'_expire', time(), 0);
		} else {
			self::$memcache->set($id.'_expire', time(), 0);
		}
		if (self::$memcache->get($id)) {
			self::$memcache->replace($id, $data, self::$_flag);
		} else {
			self::$memcache->set($id, $data, self::$_flag);
		}
		return;
	}

	/**
	 * Destroy the data for a particular session identifier in the
	 * SessionHandler backend.
	 *
	 * @access public
	 * @param string $id  The session identifier.
	 * @return boolean  True on success, false otherwise.
	 */
	public function destroySession($id)	{
		$id = 'sess_'.$id;
		self::$memcache->delete($id.'_expire');
		return self::$memcache->delete($id);
	}

    /**
	 * Garbage collect stale sessions from the SessionHandler backend.
	 *
	 * @param integer $maxlifetime  The maximum age of a session.
	 * @return boolean  True on success, false otherwise.
	 */
	public function gcSession($maxlifetime = null) {
		return true;
	}

    /**
     * Private method to delete expired session item or replace value of the existing session item
     *
     * @param string $key
     */
    private function _setExpire($key) {
		$lifetime = ini_get("session.gc_maxlifetime");
		$expire = self::$memcache->get($key.'_expire');
		if ($expire + $lifetime < time()) {
			self::$memcache->delete($key);
			self::$memcache->delete($key.'_expire');
		} else {
			self::$memcache->replace($key.'_expire', time());
		}
	}

}