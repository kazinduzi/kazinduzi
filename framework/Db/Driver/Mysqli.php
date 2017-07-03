<?php
namespace Kazinduzi\Db\Driver;

use Kazinduzi\Core\Kazinduzi;
use Kazinduzi\Db\Database;
use Exception;

/*
 * Description of Driver_mysqli
 *
 * [mysqli], [mysqli_driver] classes ALREADY pre-defined in the system, can't be redeclared
 *
 * @author Emmanuel_Leonie
 */
class Mysqli extends Database
{
    /**
     * @var array the abstract column types mapped to physical column types.
     */
    public $COLUMN_TYPES = [
        'pri'          => 'int(11) NOT null AUTO_INCREMENT PRIMARY KEY',
        'string'       => 'varchar(255)',
        'text'         => 'text',
        'integer'      => 'int(11)',
        'float'        => 'float',
        'decimal'      => 'decimal',
        'datetime'     => 'datetime',
        'timestamp'    => 'timestamp',
        'time'         => 'time',
        'date'         => 'date',
        'binary'       => 'blob',
        'boolean'      => 'tinyint(1)',
    ];

    /**
     * The DB driver name.
     *
     * @var string
     */
    public $name = 'mysqli';

    /**
     * property to hold the value of MYLSQLI_INSERTED_ID.
     *
     * @var int
     */
    public $inserted_id;

    /**
     * property to hold the MYSQLI_NUM_ROWS.
     *
     * @var int
     */
    public $num_rows;

    /**
     * Flag to chech if the multi query is required.
     *
     * @var bool
     */
    private $is_multi_query = false;

    /**
     * Options for the connection db.
     *
     * @var array
     */
    protected $options = [];

    /**
     * The null date string for Mysqli with format 000-00-00 00:00:00.
     *
     * @var string
     */
    protected $nullDate = '0000-00-00 00:00:00';

    /**
     * Constructor method to create an instance of Database.
     *
     * @param array of options
     */
    final public function __construct(array $params)
    {
        $this->options = $params;
        $this->connect();
        if (!isset($this->options['db_auto_shutdown']) || $this->options['db_auto_shutdown']) {
            register_shutdown_function([$this, 'close']);
        }
    }

    /**
     * Starts a SQL transaction.
     *
     * @link http://dev.mysql.com/doc/refman/5.0/en/set-transaction.html
     *
     * @param string Isolation level
     *
     * @return bool
     */
    public function begin($mode = null)
    {
        $this->conn || $this->connect();
        if ($mode && !mysqli_query($this->conn, "SET TRANSACTION ISOLATION LEVEL $mode")) {
            throw new Exception(mysqli_errno($this->conn).mysqli_error($this->conn));
        }
        return (bool) mysqli_query($this->conn, 'START TRANSACTION');
    }

    /**
     * Auto-commit for MySqli.
     *
     * @param bool $mode
     *
     * @return ressource
     */
    public function autocommit($mode = true)
    {
        $this->conn || $this->connect();
        return mysqli_query($this->conn, 'SET @@autocommit = '.($mode === true ? 1 : 0));
    }

    /**
     * Commit a SQL transaction.
     *
     * @param string Isolation level
     *
     * @return bool
     */
    public function commit()
    {
        $this->conn || $this->connect();
        mysqli_query($this->conn, 'COMMIT');
        $this->autocommit(true);
    }

    /**
     * Rollback a SQL transaction.
     *
     * @param string Isolation level
     *
     * @return bool
     */
    public function rollback()
    {
        $this->conn || $this->connect();
        mysqli_query($this->conn, 'ROLLBACK');
        $this->autocommit(true);
    }

    /**
     * Destructor of the class mysqli database.
     */
    public function close()
    {
        if (isset($this->result) && is_resource($this->result)) {
            mysqli_free_result($this->result);
        }
        if ($this->conn) {
            mysqli_close($this->conn);
            $this->conn = null;
        }
    }

    /**
     * Determines UTF support.
     *
     * @return bool True - UTF is supported
     */
    public function hasUTF()
    {
        $version = explode('.', $this->version());

        return ($version[0] == 5) or ($version[0] == 4 and $version[1] == 1 and (int) $version[2] >= 2);
    }

    /**
     * Custom settings for UTF support.
     *
     * @return mysql resource
     */
    public function setUTF()
    {
        if ($this->hasUTF()) {
            return mysqli_query($this->conn, "SET NAMES 'utf8'");
        }
    }

    /**
     * Checks if Mysqli is supported.
     *
     * @return true if function mysqli exists, otherwise false
     */
    public function enabled()
    {
        return extension_loaded('mysqli');
    }

    /**
     * Method that makes connection to the Mysql Server.
     *
     * @throws Exception
     *
     * @return mixed the connection id
     */
    protected function connect()
    {
        if ($this->conn) {
            return;
        }
        if (!$this->enabled()) {
            throw new Exception('mysqli extension not loaded');
        }
        if (empty($this->options['db_port'])) {
            $this->options['db_port'] = @ini_get('mysqli.default_port');
            // After that try set up the port for the next connection to the mysqli, unless the the port is provided into de 'database' config file
            Kazinduzi::config('database')->set('db_port', $this->options['db_port']);
        }
        try {
            $this->conn = mysqli_init();
            // Set autocommit to false on connecting to the database
            mysqli_options($this->conn, MYSQLI_INIT_COMMAND, 'SET AUTOCOMMIT=0');
            mysqli_options($this->conn, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
            // real connect to server
            $connected = mysqli_real_connect($this->conn, $this->options['db_host'], $this->options['db_user'], $this->options['db_password'], $this->options['db_name'], $this->options['db_port']);
        } catch (Exception $e) {
            throw new Exception('DB fails to connect');
        }
        // check if there is connection, otherwise throw exception
        if ($connected === false || mysqli_connect_errno()) {
            $this->close();
            throw new Exception(mysqli_connect_error());
        }
    }

    /**
     * Allocates and initializes a statement object suitable for mysqli_stmt_prepare().
     *
     * @see http://php.net/manual/en/mysqli.stmt-init.php
     *
     * @return object
     */
    public function getStatement()
    {
        if ($this->connected() === false) {
            $this->conn = $this->connect();
        }

        return $this->conn->stmt_init();
    }

    /**
     * Method to perform a reconnection to the mysql server,
     * if the connection has been lost.
     *
     * @return mixed
     */
    public function reconnect()
    {
        if ($this->connected() === false) {
            $this->conn = $this->connect();
        }

        return $this->conn;
    }

    /**
     * Determines if the connection to the server is active.
     *
     * @return bool true if active, otherwise false
     */
    public function connected()
    {
        if ($this->conn instanceof self) {
            return mysqli_ping($this->conn);
        }

        return false;
    }

    /**
     * get the charset of the used mysqli connection.
     *
     * @return string
     */
    protected function get_charset()
    {
        return mysqli_character_set_name($this->conn);
    }

    /**
     * set the charset of the mysqli client.
     *
     * @param string $charset
     * @param string $collation
     *
     * @return mysqli_result set
     */
    protected function db_setCharset($charset, $collation)
    {
        return mysqli_query($this->conn, 'SET NAMES '.$this->escape($charset).' COLLATE '.$this->escape($collation));
    }

    /**
     * get the last inserted id.
     *
     * @return mixed
     */
    public function insert_id()
    {
        return mysqli_insert_id($this->conn);
    }

    /**
     * get the affected rows.
     *
     * @return mixed
     */
    public function affected_rows()
    {
        return mysqli_affected_rows($this->conn);
    }

    /**
     * Escape string before it is to be sent to MySQL.
     *
     * @see http://php.net/manual/en/mysqli.real-escape-string.php
     *
     * @param type $str
     *
     * @return type
     */
    public function real_escape_string($str)
    {
        return mysqli_real_escape_string($this->conn, $str);
    }

    /**
     * Get information about the most recently executed query.
     *
     * @see http://www.php.net/manual/en/mysqli.info.php
     *
     * @return string
     */
    public function info()
    {
        return (string) mysqli_info($this->conn);
    }

    /**
     * Returns a string representing the type of connection used.
     *
     * @see http://www.php.net/manual/en/mysqli.get-host-info.php
     *
     * @return string
     */
    public function host_info()
    {
        return (string) mysqli_get_host_info($this->conn);
    }

    /**
     * @see http://www.php.net/manual/en/mysqli.get-proto-info.php
     *
     * @return string
     */
    public function protocol_version()
    {
        return mysqli_get_proto_info($this->conn);
    }

    /**
     * @see http://www.php.net/manual/en/mysqli.get-server-version.php
     *
     * @return string
     */
    public function version()
    {
        $version = mysqli_get_server_version($this->conn);
        $major = (int) ($version / 10000);
        $minor = (int) ($version % 10000 / 100);
        $revision = (int) ($version % 100);

        return $major.'.'.$minor.'.'.$revision;
    }

    /**
     * @see http://www.php.net/manual/en/mysqli.thread-id.php
     *
     * @return int
     */
    public function thread_id()
    {
        return mysqli_thread_id($this->conn);
    }

    /**
     * @see http://www.php.net/manual/en/mysqli.kill.php
     *
     * @return bool
     */
    public function kill()
    {
        return mysqli_kill($this->conn, $this->thread_id());
    }

    /**
     * @see http://www.php.net/manual/en/mysqli.refresh.php
     *
     * @return int
     */
    public function refresh()
    {
        return mysqli_refresh($this->conn, MYSQLI_REFRESH_TABLES);
    }

    /**
     * Method to grab the active processids.
     *
     * @return array
     */
    public function getProcessList()
    {
        $return = [];
        $this->setQuery('SHOW FULL PROCESSLIST')->execute();
        foreach ($result = $this->fetchAll() as $process) {
            $return[] = $process;
        }
        $this->clear();

        return $return;
    }

    /**
     * Get the type of the column f.
     *
     * @param string $type
     *
     * @return string
     */
    protected function columnType($type)
    {
        $type = strtolower($type);
        if (isset($this->COLUMN_TYPES[$type])) {
            return $this->COLUMN_TYPES[$type];
        } elseif (($pos = strpos($type, ' ')) !== false) {
            $t = substr($type, 0, $pos);

            return (isset($this->COLUMN_TYPES[$t]) ? $this->COLUMN_TYPES[$t] : $t).substr($type, $pos);
        } else {
            return $type;
        }
    }

    /**
     * get a list of all the tables in the database.
     *
     * @return array
     */
    public function getTableList()
    {
        $return = [];
        $db_fieldName = 'Tables_in_'.$this->getCfg('db_name');
        $this->setQuery('SHOW TABLES');
        foreach ($result = $this->fetchAll() as $db_tbl) {
            $return[] = $db_tbl[$db_fieldName];
        }
        $this->clear();

        return $return;
    }

    /**
     * Retrieves information about the given tables.
     *
     * @param	array|string	A table name or a list of table names
     * @param	bool		Only return field types, default true
     *
     * @return array An array of fields by table
     */
    public function getTableFields($tables, $type = true)
    {
        $tables = (array) $tables;
        $result = [];
        foreach ($tables as $tbl) {
            $this->setQuery('SHOW FIELDS FROM '.strtolower($this->quoteTable($tbl)));
            $fields = $this->fetchObjectList();
            if (true === $type) {
                foreach ($fields as $field) {
                    $result[$tbl][$field->Field] = preg_replace('/[(0-9)]/', '', $field->Type);
                }
            } else {
                foreach ($fields as $field) {
                    $result[$tbl][$field->Field] = $field;
                }
            }
        }
        $this->clear();

        return $result;
    }

    /**
     * get a list of all the tables in the database.
     *
     * @return array
     */
    public function tableList($dbName = null)
    {
        $return = [];
        $db_fieldName = 'Tables_in_'.$this->getCfg('db_name');
        is_null($dbName) ? $this->setQuery('SHOW TABLES') : $this->setQuery('SHOW TABLES FROM `'.(string) $dbName.'`');
        foreach ($result = $this->fetchAll() as $dbTable) {
            $return[] = $dbTable[$db_fieldName];
        }
        $this->clear();

        return $return;
    }

    /**
     * Retrieves information about the given tables.
     *
     * @param	array|string	A table name or a list of table names
     * @param	bool		Only return field types, default true
     *
     * @return array An array of fields by table
     */
    public function tableFields($tables, $type = true)
    {
        return $this->getTableFields($tables, $type);
    }

    /**
     * Builds a SQL statement for creating a DB table.
     *
     * @param string $table   the table to be renamed. The name will be properly quoted by the method.
     * @param string $newName the new table name. The name will be properly quoted by the method.
     *
     * @return string the SQL statement for renaming a DB table.
     */
    protected function createTableQuery($table, $columns, $options = null, $temp = false)
    {
        $cols = [];
        foreach ($columns as $name => $type) {
            if (is_string($name)) {
                $cols[] = "\t".$this->quoteColumn($name).' '.$this->columnType($type);
            } else {
                $cols[] = "\t".$type;
            }
        }
        if (false === $temp) {
            $qry = 'CREATE TABLE '.$this->quoteTable($table)." (\n".implode(",\n", $cols)."\n)";
        } else {
            $qry = 'CREATE TEMPORARY TABLE '.$this->quoteTable($table)." (\n".implode(",\n", $cols)."\n)";
        }

        return $qry = ($options === null) ? $qry : $qry.' '.$options;
    }

    /**
     * Builds a SQL statement for renaming a DB table.
     *
     * @param string $table   the table to be renamed. The name will be properly quoted by the method.
     * @param string $newName the new table name. The name will be properly quoted by the method.
     *
     * @return string the SQL statement for renaming a DB table.
     */
    public function renameTableQuery($table, $newName)
    {
        return 'RENAME TABLE '.$this->quoteTable($table).' TO '.$this->quoteTable($newName);
    }

    /**
     * get a query to drop a mysql table.
     *
     * @param string $table
     *
     * @return string
     */
    public function dropTableQuery($table)
    {
        return 'DROP TABLE '.$this->quoteTable($table);
    }

    /**
     * get a query to truncate a mysql table.
     *
     * @param string $table
     *
     * @return string
     */
    public function truncateTableQuery($table)
    {
        return 'TRUNCATE TABLE '.$this->quoteTable($table);
    }

    /**
     * get a query to alter.
     *
     * @param string $table
     * @param string $column
     * @param string $type
     *
     * @return string
     */
    public function alterColumnQuery($table, $column, $type)
    {
        $type = $this->getColumnType($type);

        return 'ALTER TABLE '.$this->quoteTable($table).' CHANGE '
                .$this->quoteColumn($column).' '
                .$this->quoteColumn($column).' '
                .$this->getColumnType($type);
    }

    /**
     * @param type $table
     * @param type $name
     * @param type $newName
     *
     * @return type
     */
    public function renameColumnQuery($table, $name, $newName)
    {
        return 'ALTER TABLE '.$this->quoteTableName($table)
                .' RENAME COLUMN '.$this->quoteColumn($name)
                .' TO '.$this->quoteColumn($newName);
    }

    /**
     * @param type $table
     * @param type $column
     *
     * @return type
     */
    public function dropColumnQuery($table, $column)
    {
        return 'ALTER TABLE '.$this->quoteTable($table)
                .' DROP COLUMN '.$this->quoteColumn($column);
    }

    /**
     * @param type $table
     * @param type $column
     * @param type $type
     *
     * @return string
     */
    public function addColumnQuery($table, $column, $type)
    {
        $type = $this->getColumnType($type);
        $sql = 'ALTER TABLE '.$this->quoteTable($table)
                .' ADD '.$this->quoteColumn($column).' '
                .$this->getColumnType($type);

        return $sql;
    }

    /**
     * @param type $name
     * @param type $table
     * @param type $columns
     * @param type $refTable
     * @param type $delete
     * @param type $update
     *
     * @return type
     */
    public function addForeignKeyQuery($name, $table, $columns, $refTable, $refColumns, $delete = null, $update = null)
    {
        $columns = preg_split('/\s*,\s*/', $columns, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($columns as $i => $col) {
            $columns[$i] = $this->quoteColumn($col);
        }
        $refColumns = preg_split('/\s*,\s*/', $refColumns, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($refColumns as $i => $col) {
            $refColumns[$i] = $this->quoteColumn($col);
        }
        $sql = 'ALTER TABLE '.$this->quoteTable($table)
                .' ADD CONSTRAINT '.$this->quoteColumn($name)
                .' FOREIGN KEY ('.implode(', ', $columns).')'
                .' REFERENCES '.$this->quoteTable($refTable)
                .' ('.implode(', ', $refColumns).')';
        if ($delete !== null) {
            $sql .= ' ON DELETE '.$delete;
        }
        if ($update !== null) {
            $sql .= ' ON UPDATE '.$update;
        }

        return $sql;
    }

    /**
     * Returns a query to drop a foreign key.
     *
     * @param string $name
     * @param string $table
     *
     * @return string
     */
    public function dropForeignKeyQuery($name, $table)
    {
        return 'ALTER TABLE '.$this->quoteTableName($table).' DROP CONSTRAINT '.$this->quoteColumnName($name);
    }

    /**
     * @param type $name
     * @param type $table
     * @param type $column
     * @param type $unique
     *
     * @return type
     */
    public function createIndexQuery($name, $table, $column, $unique = false)
    {
        $cols = [];
        $columns = preg_split('/\s*,\s*/', $column, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($columns as $col) {
            $cols[] = $this->quoteColumnName($col);
        }

        return ($unique ? 'CREATE UNIQUE INDEX ' : 'CREATE INDEX ')
                .$this->quoteTableName($name).' ON '
                .$this->quoteTableName($table).' ('.implode(', ', $cols).')';
    }

    /**
     * @param type $name
     * @param type $table
     *
     * @return type
     */
    public function dropIndexQuery($name, $table)
    {
        return 'DROP INDEX '.$this->quoteTableName($name).' ON '.$this->quoteTableName($table);
    }


    public function testAR()
    {
        echo $this->sql, '<br/>';
        $this->clear();
    }

    /**
     * @param type $real
     *
     * @throws Exception
     *
     * @return bool
     */
    public function query($real = true)
    {
        if (!$this->conn instanceof self) {
            $this->connect();
        }
        // Take a local copy so that we don't modify the original query and cause issues later
        $sql = empty($sql) ? (string) $this->sql : $this->sql = $sql;
        if ($this->limit > 0 || $this->offset > 0) {
            $sql .= ' LIMIT '.$this->offset.', '.$this->limit;
        }
        if ($this->debug) {
            $this->ticker++;
            $this->log[] = $sql;
        }
        $this->errorNum = 0;
        $this->errorMsg = '';

        /* Perfom real_query for speed */
        if ($real) {
            if (!mysqli_real_query($this->conn, $sql)) {
                throw new Exception(mysqli_error($this->conn)." SQL=$sql");
            } else {
                $this->cursor = $this->result = mysqli_store_result($this->conn);
            }
        } else {
            if (($this->cursor = $this->result = mysqli_query($this->conn, $sql)) == false) {
                throw new Exception(mysqli_error($this->conn)." SQL=$sql");
            }
        }

        if (!$this->cursor) {
            $this->errorNum = mysqli_errno($this->conn);
            $this->errorMsg = mysqli_error($this->conn)." SQL=$sql";

            return false;
        }

        return $this->result;
    }

    /**
     * Execute a batch query.
     *
     * @return mixed A database resource if successful, false if not.
     */
    public function db_queryBatch($abort_on_error = true, $p_transaction_safe = false)
    {
        $sql = (string) $this->sql;
        $this->errorNum = 0;
        $this->errorMsg = '';
        if ($p_transaction_safe) {
            $sql = rtrim($sql, "; \t\r\n\0");
            $ver = $this->version();
            preg_match_all("/(\d+)\.(\d+)\.(\d+)/i", $ver, $m);
            if ($m[1] >= 4) {
                $sql = 'START TRANSACTION;'.$sql.'; COMMIT;';
            } elseif ($m[2] >= 23 && $m[3] >= 19) {
                $sql = 'BEGIN WORK;'.$sql.'; COMMIT;';
            } elseif ($m[2] >= 23 && $m[3] >= 17) {
                $sql = 'BEGIN;'.$sql.'; COMMIT;';
            }
        }
        $query_split = $this->splitSql($sql);
        $error = 0;
        foreach ($query_split as $command_line) {
            $command_line = trim($command_line);
            if ($command_line !== '') {
                $this->cursor = mysqli_query($this->conn, $command_line) or die(mysqli_error($this->conn)." SQL=$sql");
                if ($this->debug) {
                    $this->ticker++;
                    $this->log[] = $command_line;
                }
                if (!$this->cursor) {
                    $error = 1;
                    $this->errorNum .= mysqli_errno($this->conn).' ';
                    $this->errorMsg .= mysqli_error($this->conn)." SQL=$command_line <br />";
                    if ($abort_on_error) {
                        return $this->cursor;
                    }
                }
            }
        }

        return $error ? false : true;
    }

    /**
     * Alias method to query method.
     *
     * @param string $sql
     * @param bool   $real
     *
     * @throws Exception
     *
     * @return bool
     */
    public function execute($sql = null, $real = true)
    {
        if (!$this->conn instanceof self) {
            $this->connect();
        }
        //Take a local copy so that we don't modify the original query and cause issues later
        $this->sql = $sql = empty($sql) ? (string) $this->sql : $sql;
        //
        if ($this->limit > 0 || $this->offset > 0) {
            $sql .= ' LIMIT '.$this->offset.', '.$this->limit;
        }
        //
        if ($this->debug) {
            $this->ticker++;
            $this->log[] = $sql;
        }
        //
        $this->errorNum = 0;
        $this->errorMsg = '';

        /* Perfom real_query for speed */
        if ($real) {
            if (!mysqli_real_query($this->conn, $sql)) {
                throw new Exception(mysqli_error($this->conn)." SQL=$sql");
            } else {
                $this->cursor = $this->result = mysqli_store_result($this->conn);
            }
        } else {
            if (($this->cursor = $this->result = mysqli_query($this->conn, $sql)) == false) {
                throw new Exception(mysqli_error($this->conn)." SQL=$sql");
            }
        }

        if (!$this->result) {
            $this->errorNum = mysqli_errno($this->conn);
            $this->errorMsg = mysqli_error($this->conn)." SQL=$sql";

            return false;
        }

        return $this->result;
    }

    /**
     * method to escape a query.
     *
     * @param string $str
     *
     * @return string
     */
    public function escape($str)
    {
        if (is_bool($str)) {
            $str = ($str === false) ? 0 : 1;
        } elseif (is_null($str)) {
            $str = 'null';
        } elseif (is_string($str) && !is_numeric($str) || is_array($str)) {
            $str = "'".$this->_escape_($str)."'";
        }

        return $str;
    }

    /**
     * Special escape method for LIKE query.
     *
     * @param string $str
     *
     * @return string
     */
    public function escape_like($str)
    {
        return $this->_escape_($str, true);
    }

    /**
     * Private method to execute the escaping the string.
     *
     * @param string $str
     * @param bool   $like
     *
     * @return string
     */
    private function _escape_($str, $like = false)
    {
        if (is_array($str)) {
            foreach ($str as $key => $val) {
                $str[$key] = $this->_escape_($val, $like);
            }

            return $str;
        }
        if (function_exists('mysqli_real_escape_string') && is_resource($this->conn)) {
            $str = mysqli_real_escape_string($this->conn, $str);
        } else {
            $str = addslashes($str);
        }
        // escape LIKE condition wildcards
        if ($like === true) {
            $str = str_really_escape($str);
        }

        return $str;
    }

    /**
     * Method to fetch (Assoc) mysql result.
     *
     * @param string $q
     *
     * @throws Exception
     *
     * @return array
     */
    public function fetchAssoc($q = null)
    {
        if (!$this->execute($q)) {
            throw new Exception('No query to execute');
        }
        $res = [];
        while ($row = mysqli_fetch_assoc($this->result)) {
            $res[] = $row;
        }
        mysqli_free_result($this->result);

        return $res;
    }

    /**
     * Method to fetch (Array) mysql result.
     *
     * @param string $q
     *
     * @throws Exception
     *
     * @return array
     */
    public function fetchArray($q = null)
    {
        if (!$this->execute($q)) {
            throw new Exception('No query to execute');
        }
        $res = [];
        while ($row = mysqli_fetch_array($this->result)) {
            $res[] = $row;
        }
        mysqli_free_result($this->result);

        return $res;
    }

    /**
     * Method to fetch (Object) mysql result.
     *
     * @param string $q
     *
     * @throws Exception
     *
     * @return array
     */
    public function fetchObject($q = null)
    {
        if (!$this->execute($q)) {
            throw new Exception('No query to execute');
        }
        $res = [];
        while ($row = mysqli_fetch_object($this->result)) {
            $res[] = $row;
        }

        return $res;
    }

    /**
     * The number of rows returned from the most recent query.
     *
     * @return int
     */
    public function getNumRows($cursor = null)
    {
        return mysqli_num_rows($cursor ? $cursor : $this->cursor);
    }

    /**
     * Diagnostic function.
     *
     * @return string
     */
    public function explain()
    {
        $temp = $this->sql;
        $this->sql = "EXPLAIN $this->sql";
        if (!$this->execute()) {
            throw new Exception('No query to execute');
        }
        $first = true;
        $buffer = '<table id="explain-sql">';
        $buffer .= '<thead><tr><td colspan="99">'.$this->getQueryString().'</td></tr>';
        while ($row = mysqli_fetch_assoc($this->result)) {
            if ($first) {
                $buffer .= '<tr>';
                foreach ($row as $k => $v) {
                    $buffer .= '<th>'.$k.'</th>';
                }
                $buffer .= '</tr></thead><tbody>';
                $first = false;
            }
            $buffer .= '<tr>';
            foreach ($row as $k => $v) {
                $buffer .= '<td>'.$v.'</td>';
            }
            $buffer .= '</tr>';
        }
        $buffer .= '</tbody></table>';
        mysqli_free_result($this->result);
        $this->sql = $temp;

        return $buffer;
    }

    /**
     * Load all assoc list of database rows.
     *
     * @return array A sequential list of returned records.
     */
    public function fetchAll($q = null, $type = MYSQLI_ASSOC)
    {
        if (!$this->execute($q)) {
            throw new Exception('No query to execute');
        }
        $ret = null;
        if (($row = mysqli_fetch_all($this->result, (int) $type))) {
            $ret = $row;
        }
        mysqli_free_result($this->result);

        return $ret;
    }

    /**
     * Perform data seek.
     *
     * @param int $offset
     *
     * @return mixed
     */
    public function data_seek($offset = 0)
    {
        return mysqli_data_seek($this->result, $offset);
    }

    /**
     * This method loads the first field of the first row returned by the query.
     *
     * @return mixed The value returned in the query or null if the query failed.
     */
    public function fetchArrayRow()
    {
        if (!$this->execute()) {
            throw new Exception('No Result ressource');
        }
        $ret = null;
        if (($row = mysqli_fetch_array($this->result))) {
            $ret = $row;
        }
        mysqli_free_result($this->result);

        return $ret;
    }

    /**
     * Fetch a list of result.
     *
     * @throws Exception
     *
     * @return array
     */
    public function fetchArrayList()
    {
        if (!$this->execute()) {
            throw new Exception('No Result ressource');
        }
        $array = [];
        while ($row = mysqli_fetch_array($this->result)) {
            $array[] = $row;
        }
        mysqli_free_result($this->result);

        return $array;
    }

    /**
     * Fetch a result row as an associative array.
     *
     * @return array
     */
    public function fetchAssocRow()
    {
        if (!$this->execute()) {
            throw new Exception('No Result ressource');
        }
        $ret = null;
        if ($array = mysqli_fetch_assoc($this->result)) {
            $ret = $array;
        }
        mysqli_free_result($this->result);

        return $ret;
    }

    /**
     * Fetch a assoc list of database rows.
     *
     * @param	string	The field name of a primary key
     * @param	string	An optional column name. Instead of the whole row, only this column value will be in the return array.
     *
     * @return array If <var>key</var> is empty as sequential list of returned records.
     */
    public function fetchAssocList($key = null, $column = null)
    {
        if (!$this->execute()) {
            throw new Exception('No Result ressource');
        }
        $array = [];
        while ($row = mysqli_fetch_assoc($this->result)) {
            $value = ($column) ? (isset($row[$column]) ? $row[$column] : $row) : $row;
            if ($key) {
                $array[$row[$key]] = $value;
            } else {
                $array[] = $value;
            }
        }
        mysqli_free_result($this->result);

        return $array;
    }

    /**
     * This global function fetchs the first row of a query into an object.
     *
     * @param	string	The name of the class to return (stdClass by default).
     *
     * @return object
     */
    public function fetchObjectRow($className = 'stdClass')
    {
        if (!$this->execute()) {
            throw new Exception('No Result ressource');
        }
        $ret = null;
        if ($object = mysqli_fetch_object($this->result, $className)) {
            $ret = $object;
        }
        mysqli_free_result($this->result);

        return $ret;
    }

    /**
     * Fetch a list of database into objects
     * If <var>key</var> is not empty then the returned array is indexed by the value
     * the database key.  Returns <var>null</var> if the query fails.
     *
     * @param	string	The field name of a primary key
     * @param	string	The name of the class to return (stdClass by default).
     *
     * @return array If <var>key</var> is empty as sequential list of returned records.
     */
    public function fetchObjectList($key = '', $className = 'stdClass')
    {
        if (!$this->execute()) {
            throw new Exception('No Result ressource');
        }
        $array = [];
        while ($row = mysqli_fetch_object($this->result, $className)) {
            if ($key) {
                $array[$row->$key] = $row;
            } else {
                $array[] = $row;
            }
        }
        mysqli_free_result($this->result);

        return $array;
    }

    /**
     * Fetch a row of database as in an row.
     *
     * @return The first row of the query.
     */
    public function fetchRow()
    {
        if (!$this->execute()) {
            throw new Exception('No Result ressource');
        }
        $ret = null;
        if ($row = mysqli_fetch_row($this->result)) {
            $ret = $row;
        }
        mysqli_free_result($this->result);

        return $ret;
    }

    /**
     * Fetchs a list of database rows (numeric column indexing)
     * If <var>key</var> is not empty then the returned array is indexed by the value
     * the database key.  Returns <var>null</var> if the query fails.
     *
     * @param	string	The field name of a primary key
     *
     * @return array If <var>key</var> is empty as sequential list of returned records.
     */
    public function fetchRowList($key = null)
    {
        if (!$this->execute()) {
            throw new Exception('No Result ressource');
        }
        $array = [];
        while ($row = mysqli_fetch_row($this->result)) {
            if ($key !== null) {
                $array[$row[$key]] = $row;
            } else {
                $array[] = $row;
            }
        }
        mysqli_free_result($this->result);

        return $array;
    }

    /**
     * Executes the multi-query.
     *
     * @param string $sql
     *
     * @throws Exception
     *
     * @return bool
     */
    public function multiExecute($sql)
    {
        if (!$this->conn instanceof self) {
            $this->connect();
        }

        if ($this->debug) {
            $this->ticker++;
            $this->log[] = $sql;
        }
        $this->errorNum = 0;
        $this->errorMsg = '';

        if (!mysqli_multi_query($this->conn, $sql)) {
            throw new Exception(mysqli_error($this->conn)." SQL=$sql");
        } else {
            $this->is_multi_query = true;

            return $this;
        }
    }

    /**
     * Fetchs an mysqli_fetch_associative array of result set for a multi query.
     *
     * @throws Exception
     *
     * @return array result set for multi queries
     */
    public function fetchMultiAssoc()
    {
        if (!$this->is_multi_query) {
            throw new Exception('The SQL query is not multi-query');
        }
        $rv = [];
        do {
            if (($this->result = mysqli_store_result($this->conn))) {
                while ($row = mysqli_fetch_assoc($this->result)) {
                    $rv[] = $row;
                }
                mysqli_free_result($this->result);
            }
        } while (mysqli_more_results($this->conn) && mysqli_next_result($this->conn));

        return $rv;
    }

    /**
     * Fetchs an mysqli_fetch_array array of result set for a multi query.
     *
     * @throws Exception
     *
     * @return array result set for multi queries
     */
    public function fetchMultiArray()
    {
        if (!$this->is_multi_query) {
            throw new Exception('The SQL query is not multi-query');
        }
        $rv = [];
        do {
            /* store first result set */
            if (($this->result = mysqli_store_result($this->conn))) {
                while ($row = mysqli_fetch_array($this->result)) {
                    $rv[] = $row;
                }
                mysqli_free_result($this->result);
            }
        } while (mysqli_more_results($this->conn) && mysqli_next_result($this->conn));

        return $rv;
    }

    /**
     * Fetchs an mysqli_fetch_array array of result set for a multi query.
     *
     * @throws Exception
     *
     * @return array result set for multi queries
     */
    public function fetchMultiObject($objClass = 'stdClass')
    {
        if (!$this->is_multi_query) {
            throw new Exception('The SQL query is not multi-query');
        }
        $rv = [];
        do {
            if (($this->result = mysqli_store_result($this->conn))) {
                while ($row = mysqli_fetch_object($this->result, $objClass)) {
                    $rv[] = $row;
                }
                mysqli_free_result($this->result);
            }
        } while (mysqli_more_results($this->conn) && mysqli_next_result($this->conn));

        return $rv;
    }
}
