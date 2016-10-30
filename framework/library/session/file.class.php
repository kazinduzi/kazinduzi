<?php

defined('KAZINDUZI_PATH') or die('No direct access script allowed');
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
/**
 * Description of file.
 *
 * @author Emmanuel_Leonie
 */
final class SessionFile extends Session
{
    /**
     * @var type
     */
    private static $savePath;

    /**
     * @param array $configs
     */
    public function __construct(array $configs = null)
    {
        $configs = !isset($configs) ? self::$configs : $configs;
        self::$savePath = KAZINDUZI_PATH.DIRECTORY_SEPARATOR.'tmp';
        session_save_path(self::$savePath);

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
     * Returns a value indicating whether to use custom session storage.
     * This method overrides the parent implementation and always returns true.
     *
     * @return bool whether to use custom storage.
     */
    public function getUseCustomStorage()
    {
        return true;
    }

    /**
     * @param type $savePath
     * @param type $sessionName
     *
     * @return bool
     */
    public function openSession($savePath, $sessionName)
    {
        //$savePath = self::$savePath;
        if (!is_dir(self::$savePath)) {
            mkdir(self::$savePath, 0777);
        }
        //$sessionName = self::$configs['session_name'];
        return true;
    }

    /**
     * @return bool
     */
    public function closeSession()
    {
        return true;
    }

    /**
     * @param type $id
     *
     * @return type
     */
    public function readSession($id)
    {
        $file = self::$savePath.DIRECTORY_SEPARATOR.$id.'.session';

        return file_exists($file) ? unserialize(file_get_contents($file)) : [];
    }

    /**
     * @param type $id
     * @param type $data
     *
     * @return type
     */
    public function writeSession($id, $data)
    {
        if (!is_dir(self::$savePath)) {
            mkdir(self::$savePath, 0777, true);
        }

        return file_put_contents(self::$savePath.DIRECTORY_SEPARATOR.$id.'.session', serialize($data));
    }

    /**
     * @param type $id
     *
     * @return bool
     */
    public function destroySession($id)
    {
        $sess_file = self::$savePath.DIRECTORY_SEPARATOR.$id.'.session';
        if (file_exists($sess_file)) {
            unlink($sess_file);
        }

        return true;
    }

    /**
     * @param type $maxlifetime
     *
     * @return bool
     */
    public function gcSession($maxlifetime)
    {
        foreach (glob(self::$savePath.DS.'*.session') as $sess_file) {
            if (filemtime($sess_file) + $maxlifetime < time() && file_exists($sess_file)) {
                unlink($sess_file);
            }
        }

        return true;
    }
}
