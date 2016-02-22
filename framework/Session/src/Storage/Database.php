<?php namespace Kazinduzi\Session\Storage;

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
/**
 * Description of Session_default
 *
 * @author Emmanuel_Leonie
 */

use Kazinduzi\Session\Session;
use Kazinduzi\Core\Request;

final class Database extends Session 
{
    
    const TABLE_PRIMARY_KEY_LENGTH = 128;

    /**
     *
     * @var type
     */
    private $db;

    /**
     * @var type
     */
    public $sessionTableName = 'session';

    /**
     * @var type
     */
    public $autoCreateSessionTable = true;

    /**
     *
     * @var type
     */
    private $oldSessionId;


    /**
     * Returns a value indicating whether to use custom session storage.
     * This method overrides the parent implementation and always returns true.
     * @return boolean whether to use custom storage.
     */
    public function getUseCustomStorage()
    {
        return true;
    }

    /**
     *
     * @param array $configs
     */
    public function __construct(array $configs = null) 
    {
        $configs = !isset($configs) ? self::$configs : $configs;
        if (isset($configs['session_db_name']) && $configs['session_db_name']){
            $this->sessionTableName = $configs['session_db_name'];
        }
        if (isset($configs['timeout'])){
            $this->setTimeout($configs['timeout']);
        }
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
     * Get DB
     * @return \Database
     */
    public function getDbo()
    {
	if (null === $this->db) {
	    $this->db = \Kazinduzi::db();
	}
	return $this->db;
    }

    /**
     * Creates the session DB table.
     * @param $db the database connection
     * @param string $tableName the name of the table to be created
     */
    private function createSessionTable($db, $tableName)
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
                    `id` char(" . self::TABLE_PRIMARY_KEY_LENGTH . ") not null,
                    `expire` int(10) not null,
                    `data` longtext not null,
                    `ip_address` varchar(16) not null default '0.0.0.0',
                    `user_agent` varchar(128) not null,
                    PRIMARY KEY (`id`)
                ) ENGINE=MyISAM default CHARSET=utf8;";
        $db->setQuery($sql);
        $db->execute();
    }

    /**
     * Session open handler.
     * Do not call this method directly.
     * @param string $savePath session save path
     * @param string $sessionName session name
     * @return boolean whether session is opened successfully
     */
    public function openSession($savePath, $sessionName)
    {
        if ($this->autoCreateSessionTable){
            $this->createSessionTable($this->getDbo(), $this->sessionTableName);
        }
        $sql = sprintf("DELETE FROM `%s` WHERE `expire` < %s", $this->sessionTableName, time());
        $this->getDbo()->setQuery($sql);
        return (boolean)$this->getDbo()->execute();
    }

    /**
     * Session read handler.
     * Do not call this method directly.
     * @param string $id session ID
     * @return string the session data
     */
    public function readSession($id)
    {
	$id = $this->getDbo()->real_escape_string($id);
        $sql = sprintf("SELECT * FROM `{$this->sessionTableName}` WHERE `expire` > '%d' AND `id` = '%s'", time(), $id);
        $this->getDbo()->setQuery($sql);
        $data = $this->getDbo()->fetchAssocRow();
        if (empty($data)){
            return array();
        }
        $this->oldSessionId = $data['id'];
        $this->ua = $data['user_agent'];
        $this->ip = $data['ip_address'];
        return $data['data'];
    }

    /**
     * Session write handler.
     * Do not call this method directly.
     * @param string $id session ID
     * @param string $data session data
     * @return boolean whether session write is successful
     */
    public function writeSession($id, $data){
        // When session_regenerate_id(), update sesion_id in the DB
        if ( $this->oldSessionId && $this->oldSessionId <> $id ) {
            $sql = sprintf("UPDATE `%s` SET `id` = '{$id}' WHERE `id` = '%s'", $this->sessionTableName, $this->oldSessionId);
            $this->getDbo()->setQuery($sql);
            $this->getDbo()->execute();
        }
        // exception must be caught in session write handler
        // http://us.php.net/manual/en/function.session-set-save-handler.php
        try {
	    $data = $this->getDbo()->real_escape_string($data);
	    $userAgent = $this->getDbo()->real_escape_string(Request::getInstance()->user_agent());
	    $ipAddress = $this->getDbo()->real_escape_string(Request::getInstance()->ip_address());
            $sql = sprintf("INSERT INTO `%s` (id, data, expire, ip_address, user_agent) VALUES ('%s', '%s', %s, '%s', '%s')", $this->sessionTableName, $id, $data, time() + $this->getTimeout(), $ipAddress, $userAgent) . " ON DUPLICATE KEY UPDATE `data` ='{$data}'";
            $this->getDbo()->setQuery($sql);
            $this->getDbo()->execute();
        }
        catch (Exception $e) {            
	    print_r($e);            
        }
        return true;
    }
    /**
     * Session destroy handler.
     * Do not call this method directly.
     * @param string $id session ID
     * @return boolean whether session is destroyed successfully
     */
    public function destroySession($id){
        $sql = sprintf("DELETE FROM `%s` WHERE `id` = '%s'", $this->sessionTableName, $id);
        $this->getDbo()->setQuery($sql);
        $this->getDbo()->execute();
        setcookie(session_name(), "", time() - 3600);
        return true;
    }

    /**
     * Session GC (garbage collection) handler.
     * Do not call this method directly.
     * @param integer $maxLifetime the number of seconds after which data will be seen as 'garbage' and cleaned up.
     * @return boolean whether session is GCed successfully
     */
    public function gcSession($maxLifetime = 10){
        if (!$this->getDbo()->connected()) {
            return false;
        }
        // Determine the timestamp threshold with which to purge old sessions.
        $past = time() - $maxLifetime;
        $sql = sprintf("DELETE FROM `%s` WHERE `expire` < %s", $this->sessionTableName, (int)$past);
        // Remove expired sessions from the database.
        $this->getDbo()->setQuery($sql);        
        return (boolean) $this->getDbo()->execute();
    }

}