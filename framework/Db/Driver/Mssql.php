<?php
namespace Kazinduzi\Db\Driver;

defined('KAZINDUZI_PATH') || exit('No direct script access allowed');


/**
 * Description of mssql_db
 *
 * @author Emmanuel_Leonie
 */

use Kazinduzi\Db\Database AS DB;

class Mssql extends DB {
    
    private $conn;
    
    protected $result;

    private function __construct() {
        $this->connect($params);        
        if (!isset($params['auto_shutdown']) || $params['auto_shutdown'])  {
           register_shutdown_function(array($this, 'close'));
        }
    }
    
    /**
     * 
     */
    public function enabled() {
           return extension_loaded('mssql');
    }
    
    private function connect($params) {
        /**
         * Connect to the database driver and initiate the connection_id
         */
        if (!$this->enabled()) {
            throw new Exception('mssql extension not loaded');
        }
        
        $os = $_ENV['OS'];
        
        $sep = !empty($os) && stripos($os, 'windows' !== false) ? ',' : ':';
        
        if(isset($params['db_port']) && is_numeric($params['db_port'])) 
            $params['host'] .= $sep . $params['db_port']; // Port number
        else {
            $params['host'] .= '\\' . $params['db_port']; // Named pipe
        }
        
        if($params['persistent'] != false) {
               $this->conn = mssql_connect($params['host'],$params['login'],$params['password']) or die('connection fails'.mssql_get_last_message()); 
        }
        else {
               $this->conn = mssql_pconnect($params['host'],$params['login'],$params['password']) or die('connection fails'.mssql_get_last_message()); 
        }
        /* select the database to be used */
        mssql_select_db($params['database'] , $this->conn);
    }
    
    
    
    
    
    
       
    
    
    
    
    
    
    
    
    /**
     * 
     */
    final public function close() { 
        if (isset($this->result) && is_resource($this->result)) {
            mssql_free_result($this->result);
        }
        if($this->conn != null) {            
            mssql_close($this->conn);
        }
    }   
}
