<?php defined('KAZINDUZI_PATH') || exit('No direct script access allowed');

/**
 * Description of mysql_driver
 *
 * author Emmanuel_Leonie
 */
class Driver_mysql extends Database {
    /**
     * @var array the abstract column types mapped to physical column types.
     */
    public $COLUMN_TYPES = array(
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
    );

    /**
     * The DB driver name
     * @var string
     */
    public $name = 'mysql';

    /**
     * Options for the connection db
     */
    protected $options = array();

    /**
     * The null date string for Mysql format 000-00-00 00:00:00
     * @var string
     */
    protected $nullDate = '0000-00-00 00:00:00';



    final public function __construct(array $params) {
        $this->options = $params;
        $this->connect();
        if (!isset($this->options['db_auto_shutdown']) || $this->options['db_auto_shutdown'])  {
           register_shutdown_function(array($this, 'close'));
        }
    }



    /**
	 * Start a SQL transaction
	 *
	 * @link http://dev.mysql.com/doc/refman/5.0/en/set-transaction.html
	 *
	 * @param string Isolation level
	 * @return boolean
	 */
	public function begin($mode = null) {
		// Make sure the database is connected
		$this->connected() OR $this->connect();
		if ($mode AND ! mysql_query("SET TRANSACTION ISOLATION LEVEL $mode", $this->conn)) {
			throw new Exception( "Error: " .mysql_error($this->conn) . ", Error No: " . mysql_errno($this->conn));
		}
		return (bool)mysql_query('START TRANSACTION', $this->conn);
	}

	/**
	 * Commit a SQL transaction
	 *
	 * @param string Isolation level
	 * @return boolean
	 */
	public function commit() {
		// Make sure the database is connected
		$this->connected() OR $this->connect();
		return (bool) mysql_query('COMMIT', $this->conn);
	}

	/**
	 * Rollback a SQL transaction
	 *
	 * @param string Isolation level
	 * @return boolean
	 */
	public function rollback() {
		// Make sure the database is connected
		$this->connected() OR $this->connect();
		return (bool) mysql_query('ROLLBACK', $this->conn);
	}

    /**
     *
     * @param type $mode
     * @return boolean
     */
    public function autocommit($mode = true) {
        $this->connected() OR $this->connect();
        if (!$mode) {
            return mysql_connect('START TRANSACTION', $this->conn);
        }
        return (bool) mysql_query('SET autocommit='.($mode === true ? 1 : 0) . ';', $this->conn);
    }

    /**
     *
     * @param type $qry_array
     * @return boolean
     */
    public function transaction($qry_array) {
        $retval = 1;
        $this->begin();
        foreach($qry_array as $qry) {
            $this->result = $this->query($qry);
            if(mysql_affected_rows() == 0) {
                $retval = 0;
            }
        }
        if($retval == 0) {
            $this->rollback();
            return false;
        }else{
            $this->commit();
            return true;
        }
    }

    /**
     * Close the connection upon destructing the object
     * @return none
     */
    final public function close() {
        if(isset ($this->result) && is_resource ($this->result)) {
            mysql_free_result($this->result);
        }
        if($this->conn != null || is_resource($this->conn)) {
            mysql_close($this->conn);
        }
    }

    /**
     * Determines UTF support
     * @return	boolean	True - UTF is supported
     */
    public function hasUTF() {
        // UTF is ONLY supported for MySQL 4.1.2 or high
        $ver = explode('.', $this->version());
        return ($ver[0]==5) or ($ver[0]==4 and $ver[1]==1 and (int)$ver[2]>=2);
    }

    /**
     * Custom settings for UTF support
     */
    public function setUTF() {
        if($this->hasUTF()) return @mysql_query("SET NAMES 'utf8'", $this->conn);
    }

    /**
     *
     * @return type
     */
    public function enabled() {
        return extension_loaded('mysql');
    }

    /**
     *
     * @param string $params
     * @throws Exception
     */
    private function connect() {
        if ($this->connected()) return;
        // Connect to the database driver and initiate the connection_id
        if (! $this->enabled()) {
            throw new Exception('mysql extension not loaded');
        }

        $port = null;
        if (!empty($this->options['db_port']) || is_numeric($this->options['db_port'])) {
            $port = ':'.$this->options['db_port'];
        }
        try {
            if ( $this->options['persistent'] === false ) {
                $this->conn = mysql_connect($this->options['db_host'] . $port, $this->options['db_user'], $this->options['db_password'], true);
            } else {
                $this->conn = mysql_pconnect($this->options['db_host'] . $port, $this->options['db_user'], $this->options['db_password']);
            }
            unset($port);
        } catch (Exception $e) {
            throw new Exception('DB fails to connect');
        }
        // Set sql_mode to non_strict mode
        mysql_query("SET @@SESSION.sql_mode = ''", $this->conn);
        // select the database to be used
        mysql_select_db($this->options['db_name'] , $this->conn);
        // $this->connected();

    }

    /**
     *
     * @return type
     */
    public function reconnect() {
        is_resource($this->conn) OR $this->connect();
        return $this->conn;
    }

    /**
     * Determines if the connection to the server is active.
     * @return	boolean true if active, otherwise false
     */
    public function connected() {
        if (is_resource($this->conn) && mysql_ping($this->conn)) {
            return true;
        }
        return false;
    }

    /**
     * Description
     * @return the mysql server version
     */
    public function version() {
        return @mysql_get_server_info($this->conn);
    }



    /**
     * Get the type of the column f
     * @param string $type
     * @return string
     */
    private function columnType($type) {
        $type = strtolower($type);
    	if(isset($this->COLUMN_TYPES[$type]))
            return $this->COLUMN_TYPES[$type];
    	else if(($pos = strpos($type ,' ')) !== false) {
            $t = substr($type, 0, $pos);
            return (isset($this->COLUMN_TYPES[$t]) ? $this->COLUMN_TYPES[$t] : $t) . substr($type, $pos);
    	}
    	else return $type;
    }


    /**
     * Description
     *
     * @return	array	A list of all the tables in the database
     */
    public function tableList() {
        $return = array();
        $db_fieldName = 'Tables_in_'. $this->getCfg('db_name');
        $this->setQuery('SHOW TABLES');
        foreach($result = $this->fetchAll() as $db_tbl) {
            $return[] = $db_tbl[$db_fieldName];
        }
        // Clear query after {$db->setQuery()}
        $this->clear();
        return $return;
    }

    /**
     * Retrieves information about the given tables
     *
     * @param	array|string	A table name or a list of table names
     * @param	boolean		Only return field types, default true
     * @return	array	An array of fields by table
     */
    public function tableFields($tables, $type = true) {
        // Cast the type of $tables be necessary array
        $tables = (array)$tables;
        $result = array();
        foreach($tables as $table) {
            $this->setQuery('SHOW FIELDS FROM ' . strtolower($table));
            //$fields = $this->fetchObject('SHOW FIELDS FROM ' . $table);
            $fields = $this->fetchObjectList();
            if (true === $type) {
                foreach($fields as $field) {
                    $result[$table][$field->Field] = preg_replace("/[(0-9)]/",'', $field->Type);
                }
            } else {
                foreach($fields as $field) {
                    $result[$table][$field->Field] = $field;
                }
            }
        }
        // Clear query after {$db->setQuery()}
        $this->clear();
        return $result;
    }



    /**
     * Builds a SQL statement for creating a DB table.
     * @param string $table the table to be renamed. The name will be properly quoted by the method.
     * @param string $newName the new table name. The name will be properly quoted by the method.
     * @return string the SQL statement for renaming a DB table.
     */
    protected function createTableQuery($table, $columns, $options=null, $temp=false) {
        $cols = array();
        foreach($columns as $name => $type) {
            if(is_string($name))
                $cols[]="\t".$this->quoteColumn($name).' '.$this->columnType($type);
            else
                $cols[]="\t".$type;
        }
        if(false === $temp) {
            $qry = "CREATE TABLE ".$this->quoteTable($table)." (\n".implode(",\n",$cols)."\n)";
        }
        else{
            $qry = "CREATE TEMPORARY TABLE ".$this->quoteTable($table)." (\n".implode(",\n",$cols)."\n)";
        }
        return ( $qry = $options === null) ? $qry  : $qry .' '.$options;
    }


    /**
     * Builds a SQL statement for renaming a DB table.
     * @param string $table the table to be renamed. The name will be properly quoted by the method.
     * @param string $newName the new table name. The name will be properly quoted by the method.
     * @return string the SQL statement for renaming a DB table.
     */
    public function renameTableQuery($table, $newName) {
        return 'RENAME TABLE ' . $this->quoteTable($table) . ' TO ' . $this->quoteTable($newName);
    }

    /**
     *
     * @param type $table
     * @return type
     */
    public function dropTableQuery($table) {
        return 'DROP TABLE ' . $this->quoteTable($table);
    }


    /**
     *
     * @param type $table
     * @return type
     */
    public function truncateTableQuery($table) {
        return 'TRUNCATE TABLE ' . $this->quoteTable($table);
    }


    /**
     *
     * @param type $table
     * @param type $column
     * @param type $type
     * @return type
     */
    public function alterColumnQuery($table, $column, $type) {
            $type=$this->getColumnType($type);
            return 'ALTER TABLE ' . $this->quoteTable($table) . ' CHANGE '
                    . $this->quoteColumn($column) . ' '
                    . $this->quoteColumn($column) . ' '
                    . $this->getColumnType($type);
    }

    /**
     *
     * @param type $table
     * @param type $name
     * @param type $newName
     * @return type
     */
    public function renameColumnQuery($table, $name, $newName) {
            return "ALTER TABLE ".$this->quoteTableName($table)
                    . " RENAME COLUMN ".$this->quoteColumn($name)
                    . " TO ".$this->quoteColumn($newName);
    }

    /**
     *
     * @param type $table
     * @param type $column
     * @return type
     */
    public function dropColumnQuery($table, $column) {
            return "ALTER TABLE ".$this->quoteTable($table)
                    ." DROP COLUMN ".$this->quoteColumn($column);
    }

    /**
     *
     * @param type $table
     * @param type $column
     * @param type $type
     * @return string
     */
    public function addColumnQuery($table, $column, $type) {
            $type=$this->getColumnType($type);
            $sql='ALTER TABLE ' . $this->quoteTable($table)
                    . ' ADD ' . $this->quoteColumn($column) . ' '
                    . $this->getColumnType($type);
            return $sql;
    }

    /**
     *
     * @param type $name
     * @param type $table
     * @param type $columns
     * @param type $refTable
     * @param type $delete
     * @param type $update
     * @return type
     */
    public function addForeignKeyQuery($name, $table, $columns, $refTable, $refColumns, $delete=null, $update=null) {
            $columns = preg_split('/\s*,\s*/',$columns,-1,PREG_SPLIT_NO_EMPTY);

            foreach($columns as $i => $col) {
                $columns[$i] = $this->quoteColumn($col);
            }
            $refColumns = preg_split('/\s*,\s*/',$refColumns,-1,PREG_SPLIT_NO_EMPTY);

            foreach($refColumns as $i=>$col) {
                $refColumns[$i] = $this->quoteColumn($col);
            }

            $sql='ALTER TABLE '.$this->quoteTable($table)
                    .' ADD CONSTRAINT '.$this->quoteColumn($name)
                    .' FOREIGN KEY ('.implode(', ', $columns).')'
                    .' REFERENCES '.$this->quoteTable($refTable)
                    .' ('.implode(', ', $refColumns).')';
            if($delete !== null)
                    $sql.=' ON DELETE '.$delete;
            if($update !== null)
                    $sql.=' ON UPDATE '.$update;
            return $sql;
    }

    /**
     *
     * @param type $name
     * @param type $table
     * @return type
     */
    public function dropForeignKeyQuery($name, $table) {
            return 'ALTER TABLE '.$this->quoteTableName($table)
                    .' DROP CONSTRAINT '.$this->quoteColumnName($name);
    }

    /**
     *
     * @param type $name
     * @param type $table
     * @param type $column
     * @param type $unique
     * @return type
     */
    public function createIndexQuery($name, $table, $column, $unique=false) {
            $cols=array();
            $columns=preg_split('/\s*,\s*/',$column,-1,PREG_SPLIT_NO_EMPTY);
            foreach($columns as $col)
                    $cols[]=$this->quoteColumnName($col);
            return ($unique ? 'CREATE UNIQUE INDEX ' : 'CREATE INDEX ')
                    . $this->quoteTableName($name).' ON '
                    . $this->quoteTableName($table).' ('.implode(', ',$cols).')';
    }

    /**
     *
     * @param type $name
     * @param type $table
     * @return type
     */
    public function dropIndexQuery($name, $table) {
        return 'DROP INDEX '.$this->quoteTableName($name).' ON '.$this->quoteTableName($table);
    }

    /**** TO DO HERE : BUILDING QUERIES ******/






    /**
     *
     */
    public function query($sql=null) {
        if (!is_resource($this->conn)) {
            $this->connect();
        }

        // Take a local copy so that we don't modify the original query and cause issues later
        $sql = empty($sql) ? (string)$this->sql : $this->sql = $sql;
        if($this->limit > 0 || $this->offset > 0) {
            $sql .= ' LIMIT '.$this->offset.', '.$this->limit;
        }
        //
        if($this->debug) {
            $this->ticker++;
            $this->log[] = $sql;
        }

        $this->errorNum = 0;
        $this->errorMsg = '';

        //$this->cursor = $this->result = mysql_query($sql, $this->conn);
        if (($this->cursor = $this->result = mysql_query($sql, $this->conn)) === false) {
            throw new Exception(mysql_error($this->conn)." SQL=$sql");
        }

        if (! $this->cursor) {
            $this->errorNum = mysql_errno($this->conn);
            $this->errorMsg = mysql_error($this->conn)." SQL=$sql";
            return false;
        }
        return $this->result;

    }


    /**
     *
     */
    public function execute($sql=null) {
        if (!is_resource($this->conn)) {
            $this->connect();
        }
        // Take a local copy so that we don't modify the original query and cause issues later
        $sql = empty($sql) ? (string)$this->sql : $this->sql = $sql;
        if($this->limit > 0 || $this->offset > 0) {
            $sql .= ' LIMIT '.$this->offset.', '.$this->limit;
        }
        //
        if($this->debug) {
            $this->ticker++;
            $this->log[] = $sql;
        }
        $this->errorNum = 0;
        $this->errorMsg = '';

        //$this->cursor = $this->result = mysql_query($sql, $this->conn) or die("Cannot query" . mysql_error());
        if (($this->cursor = $this->result = mysql_query($sql, $this->conn)) === false) {
            throw new Exception("Cannot query" . mysql_error());
        }

        if (! $this->cursor) {
            $this->errorNum = mysql_errno($this->conn);
            $this->errorMsg = mysql_error($this->conn)." SQL=$sql";
            return false;
        }
        return $this->result;

    }

    /**
     *
     */
    function testAR() {
        echo $this->sql, '<br/>';
        $this->clear();
    }

    /**
     *
     * @return type
     */
    public function insert_id() {
        return mysql_insert_id($this->conn);
    }

    /**
     *
     * @return type
     */
    public function affected_rows() {
        return mysql_affected_rows($this->conn);
    }



    /**
     *
     * @param type $q
     * @return type
     */
    public function fetchAssoc($q=null) {
        // Execute the sql query
        if (!$this->execute($q)) throw new Exception('No query to execute');

        $results = array();
        while($row = mysql_fetch_assoc($this->result)) {
            $results[] = $row;
        }
        mysql_free_result($this->result);
        return $results;
    }

    /**
     * Execute the query
     *
     * @return	mixed	A database resource if successful, false if not.
     */
    public function db_query() {
        $this->conneted() OR $this->connect();
        // Take a local copy so that we don't modify the original query and cause issues later
        $sql = (string) $this->getQueryString();
        if ($this->limit > 0 || $this->offset > 0) {
            $sql .= ' LIMIT '.$this->offset.', '.$this->limit;
        }
        if ($this->debug) {
            $this->ticker++;
            $this->log[] = $sql;
        }
        echo $sql;
        $this->errorNum = 0;
        $this->errorMsg = '';
        $this->cursor = mysql_query($sql, $this->conn);
        if (!$this->cursor) {
            $this->errorNum = mysql_errno($this->conn);
            $this->errorMsg = mysql_error($this->conn)." SQL=$sql";
            return false;
        }
        return $this->cursor;
    }

    /**
     * Execute a batch query
     * @return	mixed	A database resource if successful, false if not.
     */
    public function db_queryBatch($abort_on_error=true, $p_transaction_safe = false) {
        $sql = (string) $this->sql;
        $this->errorNum = 0;
        $this->errorMsg = '';
        if ($p_transaction_safe) {
            $sql = rtrim($sql, "; \t\r\n\0");
            $si = $this->version();
            preg_match_all("/(\d+)\.(\d+)\.(\d+)/i", $si, $m);
            if ($m[1] >= 4) {
                $sql = 'START TRANSACTION;' . $sql . '; COMMIT;';
            } else if ($m[2] >= 23 && $m[3] >= 19) {
                $sql = 'BEGIN WORK;' . $sql . '; COMMIT;';
            } else if ($m[2] >= 23 && $m[3] >= 17) {
                $sql = 'BEGIN;' . $sql . '; COMMIT;';
            }
        }

        $query_split = $this->splitSql($sql);
        $error = 0;
        foreach ($query_split as $command_line) {
            $command_line = trim($command_line);
            if ($command_line != '') {
                $this->cursor = mysql_query($command_line, $this->conn);
                if ($this->debug) {
                    $this->ticker++;
                    $this->log[] = $command_line;
                }
                if (!$this->cursor) {
                    $error = 1;
                    $this->errorNum .= mysql_errno($this->conn) . ' ';
                    $this->errorMsg .= mysql_error($this->conn)." SQL=$command_line <br />";
                    if ($abort_on_error) {
                        return $this->cursor;
                    }
                }
            }
        }
        return $error ? false : true;
    }

    /**
     * The number of rows returned from the most recent query.
     * @return	int
     */
    public function getNumRows($cur=null) {
         return mysql_num_rows($cur ? $cur : $this->cursor);
    }

    /**
     * Diagnostic function
     * @return	string
     */
    public function explain() {
        $temp = $this->sql;
        $this->sql = "EXPLAIN $this->sql";
        if (!($cursor = $this->query())) {
            return null;
        }
        $first = true;
        $buffer = '<table id="explain-sql">';
        $buffer .= '<thead><tr><td colspan="99">'.$this->getQueryString().'</td></tr>';
        while ($row = mysqli_fetch_assoc($cursor)) {
            if ($first) {
                $buffer .= '<tr>';
                foreach ($row as $k=>$v) {
                    $buffer .= '<th>'.$k.'</th>';
                }
                $buffer .= '</tr></thead><tbody>';
                $first = false;
            }
            $buffer .= '<tr>';
            foreach ($row as $k=>$v) {
                    $buffer .= '<td>'.$v.'</td>';
            }
            $buffer .= '</tr>';
        }
        $buffer .= '</tbody></table>';
        mysqli_free_result($cursor);
        $this->sql = $temp;
        return $buffer;
    }


    /**
     * Load all assoc list of database rows
     * @return	array	A sequential list of returned records.
     */
    public function fetchAll() {
        return $this->fetchAssocList();
    }

    /**
     * This method loads the first field of the first row returned by the query.
     *
     * @return	mixed	The value returned in the query or null if the query failed.
     */
    public function fetchResult() {
        if (!$cursor = $this->execute()) throw new Exception('No query to execute');

        $ret = null;
        if($row = mysql_fetch_row($cursor)) {
            $ret = $row[0];
        }
        mysql_free_result($cursor);
        return $ret;
    }

    /**
     * Load an array of single field results into an array
     */
    public function fetchResultArray($position=0) {
        if (!$cursor = $this->execute()) throw new Exception('No query to execute');

        $array = array();
        while($row = mysql_fetch_row($cursor)) {
            $array[] = $row[$position];
        }
        mysql_free_result($cursor);
        return $array;
    }

    /**
     * This method loads the first field of the first row returned by the query.
     *
     * @return	mixed	The value returned in the query or null if the query failed.
     */
    public function fetchArrayRow() {
        if (!$cursor = $this->execute()) throw new Exception('No query to execute');

        $ret = null;
        if (($row = mysql_fetch_array($cursor))) {
            $ret = $row[0];
        }
        mysql_free_result($cursor);
        return $ret;
    }

    /**
     * Load an array of single field results into an array
     */
    public function fetchArrayList() {
        if (!$cursor = $this->execute()) throw new Exception('No query to execute');

        $array = array();
        while($row = mysql_fetch_row($cursor)) {
            $array[] = $row;
        }
        mysql_free_result($cursor);
        return $array;
    }



    /**
     * Fetch a result row as an associative array
     * @return	array
     */
    public function fetchAssocRow() {
        if (!$cursor = $this->execute()) throw new Exception('No query to execute');
        $ret = null;
        if($array = mysql_fetch_assoc($cursor)) {
            $ret = $array;
        }
        mysql_free_result($cursor);
        return $ret;
    }

    /**
     * Fetch a assoc list of database rows
     * @param	string	The field name of a primary key
     * @param	string	An optional column name. Instead of the whole row, only this column value will be in the return array.
     * @return	array	If <var>key</var> is empty as sequential list of returned records.
     */
    public function fetchAssocList($key=null, $column=null) {
        if (!$cursor = $this->execute()) throw new Exception('No query to execute');
        $array = array();
        while($row = mysql_fetch_assoc($cursor)) {
            $value = ($column) ? (isset($row[$column]) ? $row[$column] : $row) : $row;
            if($key) {
                $array[$row[$key]] = $value;
            } else {
                $array[] = $value;
            }
        }
        mysql_free_result($cursor);
        return $array;
    }


    /**
     * This global function fetchs the first row of a query into an object
     * @param	string	The name of the class to return (stdClass by default).
     * @return	object
     */
    public function fetchObjectRow($className = 'stdClass') {
        if (!$cursor = $this->execute()) throw new Exception('No query to execute');
        $ret = null;
        if ($object = mysql_fetch_object($cursor, $className)) {
            $ret = $object;
        }
        mysql_free_result($cursor);
        return $ret;
    }

    /**
     * Fetch a list of database into objects
     * If <var>key</var> is not empty then the returned array is indexed by the value
     * the database key.  Returns <var>null</var> if the query fails.
     * @param	string	The field name of a primary key
     * @param	string	The name of the class to return (stdClass by default).
     * @return	array	If <var>key</var> is empty as sequential list of returned records.
     */
    public function fetchObjectList($key='', $className='stdClass') {
            if (!$cursor = $this->execute()) throw new Exception('No query to execute');
            $array = array();
            while ($row = mysql_fetch_object($cursor, $className)) {
                if ($key) {
                    $array[$row->$key] = $row;
                } else {
                    $array[] = $row;
                }
            }
            mysql_free_result($cursor);
            return $array;
    }

    /**
     * Fetch a row of database as in an row
     * @return The first row of the query.
     */
    public function fetchRow() {
            if (!$cursor = $this->execute()) throw new Exception('No query to execute');
            $ret = null;
            if ($row = mysql_fetch_row($cursor)) {
                $ret = $row;
            }
            mysql_free_result($cursor);
            return $ret;
    }

    /**
     * Fetchs a list of database rows (numeric column indexing)
     * If <var>key</var> is not empty then the returned array is indexed by the value
     * the database key.  Returns <var>null</var> if the query fails.
     * @param	string	The field name of a primary key
     * @return	array	If <var>key</var> is empty as sequential list of returned records.
     */
    public function fetchRowList($key=null) {
        if (!$cursor = $this->execute()) throw new Exception('No query to execute');
        $array = array();
        while ($row = mysql_fetch_row($cursor)) {
            if ($key !== null) {
                $array[$row[$key]] = $row;
            } else {
                $array[] = $row;
            }
        }
        mysql_free_result($cursor);
        return $array;
    }


    /**
     *
     */
    public function fetchArray($q=null) {
        if (!$this->execute($q)) throw new Exception('No query to execute');
        $results = array();
        while($row = mysql_fetch_array($this->result)) {
            $results[] = $row;
        }
        mysql_free_result($this->result);
        return $results;
    }

    /**
     *
     */
    public function fetchObject($q=null) {
        if (!$this->execute($q)) throw new Exception('No query to execute');
        $results = array();
        while($row = mysql_fetch_object($this->result)) {
            $results[] = $row;
        }
        mysql_free_result($this->result);
        return $results;
    }



    /**
     *
     */
    public function escape($str) {
        if ($str === null) {
            $str = 'null';
        }
        else if ($str === true) {
            $str = "'1'";
        }
        else if ($str === false) {
            $str = "'0'";
        }
        else if (is_int($str)) {
            $str = (int) $str;
        }
        else if (is_float($str)) {
            // Convert to non-locale aware float to prevent possible commas
            $str = sprintf('%F', $str);
        }
        else if (is_string($str) && !is_numeric($str) or is_array($str)) {
            $str = "'".$this->_escape_($str)."'";
        }
        return $str;
    }

    /**
     *
     */
    public function escape_like($str) {
        return $this->_escape_($str, true);
    }

    /**
     *
     */
    public function new_escape($str) {
        $str = str_replace(array('%','_','\''), array('&#37;','&#95;','&#39;'), $str);
        if (is_bool($str)) {
           $str = ($str === false) ? "'0'" : "'1'";
        }
        elseif (is_null($str)) {
           $str = 'null';
        }
        elseif (is_string($str) && !is_numeric($str) or is_array($str)) {
           $str = "'".$this->_escape_($str)."'";
        }
        return $str;
    }


    /**
     *
     */
    private function _escape_($str, $like = false) {
        $str = str_replace(array('%', '_','\''), array('&#37;','&#95;','&#39;'), $str);
        if (is_array($str)) {
            foreach($str as $key => $val) {
                $str[$key] = $this->_escape_($val, $like);
            }
            return $str;
        }

        if (function_exists('mysql_real_escape_string') AND is_resource($this->conn)) {
           $str = mysql_real_escape_string($str, $this->conn);
        }
        else{
           $str = addslashes($str);
        }
        // escape LIKE condition wildcards
        if ($like === true) {
            //$str = str_replace(array('%', '_','\''), array('\\%', '\\_','&#39;'), $str);
            $str = str_really_escape($str);
        }
        return $str;
    }


    /**
     * Retrieves information about the given tables
     *
     * @param	array|string	A table name or a list of table names
     * @param	boolean		Only return field types, default true
     * @return	array	An array of fields by table
     */
    public function getTableFields($tables, $type = true) {
        // Cast the type of $tables be necessary array
        $tables = (array)$tables;
        $result = array();
        foreach($tables as $tbl) {
            $this->setQuery('SHOW FIELDS FROM ' . strtolower($tbl));
            $fields = $this->fetchObjectList();
            if(true === $type) {
                foreach($fields as $field) {
                    $result[$tbl][$field->Field] = preg_replace("/[(0-9)]/",'', $field->Type);
                }
            }else{
                foreach($fields as $field) {
                    $result[$tbl][$field->Field] = $field;
                }
            }
        }
        // Clear query after {$db->setQuery()}
        $this->clear();
        return $result;
    }

    /**
     * Method to grab the active processids
     * @return type
     */
    public function getProcessList() {
        $return = array();
        $this->setQuery('SHOW FULL PROCESSLIST')->execute();
        foreach($result = $this->fetchAll() as $process) {
            $return[] = $process;
        }
        $this->clear();
        return $return;
    }

    /**
     * @see http://www.php.net/manual/en/mysql.thread-id.php
     * @return int
     */
    public function thread_id() {
        return mysql_thread_id($this->conn);
    }

}