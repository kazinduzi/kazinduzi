<?php

namespace Kazinduzi\Session\Storage;

/*
 * Kazinduzi Framework (http://framework.kazinduzi.com/)
 *
 * @author    Emmanuel Ndayiragije <endayiragije@gmail.com>
 * @link      http://kazinduzi.com
 * @copyright Copyright (c) 2010-2013 Kazinduzi. (http://www.kazinduzi.com)
 * @license   http://kazinduzi.com/page/license MIT License
 * @package   Kazinduzi
 */
/*
 * APC session storage handler for PHP
 *
 * @see http://www.php.net/manual/en/function.session-set-save-handler.php
 */

use Kazinduzi\Core\Request;
use Kazinduzi\Session\Session;

final class Apc extends Session
{
    /**
     * Constructor.
     *
     * @param array $options optional parameters
     */
    public function __construct(array $configs = null)
    {
        if (!extension_loaded('apc')) {
            throw new \Exception('APC extension is not available');
        }
        $configs = isset($configs) ? $configs : self::$configs;

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
     * Tell we want use custom session storage.
     *
     * @return bool
     */
    public function getUseCustomStorage()
    {
        return true;
    }

    /**
     * Open the SessionHandler backend.
     *
     * @param string $save_path    The path to the session object.
     * @param string $session_name The name of the session.
     *
     * @return bool True on success, false otherwise.
     */
    public function openSession($save_path, $session_name)
    {
        return true;
    }

    /**
     * Close the SessionHandler backend.
     *
     * @return bool True on success, false otherwise.
     */
    public function closeSession()
    {
        return true;
    }

    /**
     * Read the data for a particular session identifier from the
     * SessionHandler backend.
     *
     * @param string $id The session identifier.
     *
     * @return string The session data.
     */
    public function readSession($id)
    {
        $sess_id = 'sess_'.$id;

        return (string) apc_fetch($sess_id);
    }

    /**
     * Write session data to the SessionHandler backend.
     *
     * @param string $id   The session identifier.
     * @param string $data The session data.
     *
     * @return bool true on success, false otherwise.
     */
    public function writeSession($id, $data)
    {
        $sess_id = 'sess_'.$id;

        return apc_store($sess_id, $data, ini_get('session.gc_maxlifetime'));
    }

    /**
     * Destroy the data for a particular session identifier in the
     * SessionHandler backend.
     *
     * @param string $id The session identifier.
     *
     * @return bool True on success, false otherwise.
     */
    public function destroySession($id)
    {
        $sess_id = 'sess_'.$id;

        return apc_delete($sess_id);
    }

    /**
     * Garbage collect stale sessions from the SessionHandler backend.
     *
     * @param int $maxlifetime The maximum age of a session.
     *
     * @return bool True on success, false otherwise.
     */
    public function gcSession($maxlifetime = null)
    {
        return true;
    }
}
