<?php
namespace Kazinduzi\Session\Storage;

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

use Kazinduzi\Session\Session;
use Kazinduzi\Core\Request;

final class Memcached extends Session
{
    /**
     *
     * @var type
     */
    protected  static $memcached;

    /**
     *
     * @var type
     */
    protected static $_flag = false;

    /**
     * Constructor
     *
     * @access public
     * @param array $options optional parameters
     */
    public function __construct(array $configs = null) {
        if (!extension_loaded('memcached')) {
                throw new Exception('Memcached PHP extention not loaded');
        }
        $configs = isset($configs) ? $configs : self::$configs;
        
        // If the client 'User-Agent' is not set from the DB session, we fetch the new one from the client request
        if (!$this->ua){
            $this->ua = Request::getInstance()->user_agent();
        }
        // If the client IP-Address is not set from the DB session, we fetch the new one from the client request
        if (!$this->ip){
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
            self::$memcached = new \Memcached();
            foreach (self::$configs['servers'] as $server) {
                if (!$server) {
                    throw new \Exception('No Memcached servers defined in configuration');
                }                
                self::$memcached->addServer($server['host'], $server['port']);                
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
		return self::$memcached->quit();
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
		return self::$memcached->get($id);
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
		if (self::$memcached->get($id.'_expire')) {
                    self::$memcached->replace($id.'_expire', time());
		} else {
                    self::$memcached->set($id.'_expire', time());
		}
                
		if (self::$memcached->get($id)) {
                    self::$memcached->replace($id, $data);
		} else {
                    self::$memcached->set($id, $data);
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
		self::$memcached->delete($id.'_expire');
		return self::$memcached->delete($id);
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
		$expire = self::$memcached->get($key.'_expire');
		if ($expire + $lifetime < time()) {
			self::$memcached->delete($key);
			self::$memcached->delete($key.'_expire');
		} else {
			self::$memcached->replace($key.'_expire', time());
		}
	}

}