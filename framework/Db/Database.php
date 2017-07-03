<?php

namespace Kazinduzi\Db;

use Kazinduzi\Core\Kazinduzi;

/*
 * Kazinduzi Framework (http://framework.kazinduzi.com/)
 *
 * @author    Emmanuel Ndayiragije <endayiragije@gmail.com>
 * @link      http://kazinduzi.com
 * @copyright Copyright (c) 2010-2013 Kazinduzi. (http://www.kazinduzi.com)
 * @license   http://kazinduzi.com/page/license MIT License
 * @package   Kazinduzi
 */


class Database extends DbActiveRecord
{
    /**
     * The DB driver name.
     *
     * @var string
     */
    public $name = '';

    /**
     * The database link identifier.
     *
     * @var mixed
     */
    protected $conn = false;

    /**
     * The null date string.
     *
     * @var string
     */
    protected $nullDate = '0000-00-00 00:00:00';

    /**
     * The debug level (0 = off, 1 = on).
     */
    protected $debug = 0;

    /**
     * The number of queries performed by the object instance.
     *
     * @var int
     */
    protected $ticker = 0;

    /**
     * A log of queries.
     *
     * @var array
     */
    protected $log = [];

    /**
     * The database error number.
     *
     * @var int
     */
    protected $errorNum = 0;

    /**
     * The database error message.
     *
     * @var string
     */
    protected $errorMsg = '';

    /**
     * The limit for the query.
     *
     * @var int
     */
    protected $limit = 0;

    /**
     * The for offset for the limit.
     *
     * @var int
     */
    protected $offset = 0;

    /**
     * All queries will be kept here @var type array() of queries as array.
     */
    protected $queries = [];

    /**
     * Fields to be quoted @var array Array of fields that are going to be quoted.
     */
    protected $quoted = false;

    /**
     * Legacy compatibility.
     *
     * @var bool
     */
    protected $hasQuoted = false;

    /**
     * UTF-8 support.
     *
     * @var bool
     */
    protected $utf = false;

    /**
     * The DB singleton object to be held statically.
     *
     * @var object instance for database class
     */
    protected static $instance;
    protected static $instances = [];

    /**
     *  Cfg data to be used for the DB object @var array.
     */
    protected $config = [];

    /**
     * The last query cursor.
     *
     * @var resource
     */
    protected $cursor = false;

    /**
     * The query result from quering db.
     *
     * @var resource
     */
    protected $result = false;

    /**
     * parameter to hold the DB driver object @var Object for the specific driver class.
     */
    private $driver = false;

    /**
     * @var type
     */
    public static $db = false;

    /**
     * constructor in the singleton mode.
     */
    private function __construct()
    {
        $driverName = $this->getConfigValue('driver') ? $this->getConfigValue('driver') : 'mysqli';
        switch (strtolower($driverName)) {
            case 'mysqli':
               $this->driver = new Driver\Mysqli($this->getConfigValue());
               break;
            case 'mssql':
                $this->driver = new Driver\Mssql($this->getConfigValue());
                break;
        }
        $this->utf = $this->driver->hasUTF();
        if ($this->utf) {
            $this->driver->setUTF();
        }
    }

    /**
     * Destructor method for the current database driver.
     */
    public function __destruct()
    {
        $this->conn = false;
        if ($this->driver) {
            unset($this->driver);
        }
    }

    /**
     * Method to get the instance of the database.
     *
     * Create a singleton object of the DB
     *
     * @return object instance of the DB object
     */
    final public static function getInstance()
    {
        if (!static::$instance instanceof self) {
            static::$instance = new static();
        }

        return static::$instance->driver;
    }

    /**
     * Retrieve the configuration data for the databse.
     *
     * @param string $item
     *
     * @return array
     */
    protected function getCfg($item = null)
    {
        return $this->getCfg($item);
    }

    /**
     * get database configuration.
     *
     * @param string $item
     *
     * @return array
     */
    protected function getConfigValue($item = null)
    {
        if (empty($this->config)) {
            $this->config = Kazinduzi::getConfig('database')->toArray();
        }

        return isset($item) ? $this->config[$item] : $this->config;
    }

    /**
     * Sets the debug level on or off.
     *
     * @param	int	0 = off, 1 = on
     */
    public function debug($level)
    {
        $this->debug = (int) ($level);
    }

    /**
     * Method to be overrided from class extending __CLASS__.
     *
     * Is (mysqli|mysql|postgre|...) connector is available
     *
     * @return bool True on success, false otherwise.
     */
    protected function enabled()
    {
    }

    /**
     * Method to be overrided from class extending __CLASS__.
     *
     * Try to reconnect to DB server
     *
     * @return connection resource
     */
    protected function reconnect()
    {
    }

    /**
     * Method to be overrided from class extending __CLASS__
     * ------------------------------------------------------
     * Determines if the connection to the server is active.
     *
     * @return bool true if active, otherwise false
     */
    public function connected()
    {
    }

    /**
     * Adds a field or array of field names to the list that are to be quoted.
     *
     * @param	mixed	Field name or array of names
     */
    public function quoted($fields)
    {
        if (is_string($fields)) {
            $this->quoted[] = $fields;
        } else {
            $this->quoted = array_merge($this->quoted, (array) $fields);
        }
        $this->hasQuoted = true;
    }

    /**
     * Checks if field name needs to be quoted.
     *
     * @param	string	The field name
     *
     * @return bool
     */
    public function is_quoted($fieldName)
    {
        if ($this->hasQuoted) {
            return in_array($fieldName, $this->quoted);
        } else {
            return true;
        }
    }

    /**
     * Get the database UTF-8 support.
     *
     * @return bool
     */
    public function isUTF()
    {
        return $this->utf;
    }

    /**
     * Disable Cloning this object class.
     */
    final private function __clone()
    {
    }

    /**
     * Close the connection when serializing.
     */
    public function __sleep()
    {
        self::__destruct();

        return array_keys(get_object_vars($this));
    }

    /**
     * Magic method __wakeup where unserializing this object.
     */
    public function __wakeup()
    {
        $this->connect();
    }

    /**
     * @return type
     */
    public function execute()
    {
    }

    /*
     *
    public function query() {}
    public function db_query() {}
    public function execute() {}
    public function fields() {}
     *
     */

    /**
     * Get the connection.
     * Provides access to the underlying database connection.
     * Useful for when calling a proprietary method such as postgre's lo_* methods.
     *
     * @return resource connection
     */
    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * Get the database null date.
     *
     * @return string Quoted null/zero date string
     */
    public function getNullDate()
    {
        return $this->nullDate;
    }

    /**
     * @return object This object to support chaining.
     */
    public function setQuery($query, $offset = 0, $limit = 0)
    {
        $this->sql = (string) $query;
        $this->limit = (int) $limit;
        $this->offset = (int) $offset;

        return $this;
    }


    public function buildQuery()
    {
        $this->setQuery((string) $this);

        return $this;
    }

    /**
     * Get the current query, or new ActiveRecord query object.
     *
     * @param	bool	False to return the last query set by setQuery, True to return a new JDatabaseQuery object.
     *
     * @return string The current value of the internal SQL variable
     */
    public function getQueryAR()
    {
        // require_once('dbActiveRecord.php');
        // return DbActiveRecord::getSingleton();
        return $this;
    }

    /**
     * @return type
     */
    public function getQueryString()
    {
        if (empty($this->sql)) {
            $this->setQuery((string) $this);
        }

        return $this->sql;
    }

    /**
     * Get a quoted database.
     *
     * @param	string	A string
     * @param	bool	Default true to escape string, false to leave the string unchanged
     *
     * @return string
     */
    public function quote($text, $escaped = true)
    {
        return $escaped ? $this->escape($text) : $text;
    }

    /**
     * @param type $col
     *
     * @return type
     */
    public function quoteColumn($col)
    {
        if (is_string($col)) {
            $col = trim($col);

            return $col === '*' ? $col : '`'.$col.'`';
        }
        if (is_array($col)) {
            foreach ($col as $c) {
                $quoted[] = quoteColumn($c);
            }
        }

        return implode(',', $quoted);
    }

    /**
     * @param type $col
     *
     * @return type
     */
    public function quoteTable($col)
    {
        if (is_string($col)) {
            return '`'.$col.'`';
        }
        if (is_array($col)) {
            foreach ($col as $c) {
                $quoted[] = quoteColumn($c);
            }
        }

        return implode(',', $quoted);
    }

    /**
     * Splits a string of queries into an array of individual queries.
     *
     * @param	string	The queries to split
     *
     * @return array queries
     */
    public function splitSql($queries)
    {
        $start = 0;
        $open = false;
        $open_char = '';
        $query_split = [];
        for ($i = 0; $i < $end = strlen($queries); $i++) {
            $current = substr($queries, $i, 1);
            if ($current == '"' || $current == '\'') {
                $n = 2;
                while (substr($queries, $i - $n + 1, 1) == '\\' && $n < $i) {
                    $n++;
                }
                if ($n % 2 == 0) {
                    if ($open) {
                        if ($current == $open_char) {
                            $open = false;
                            $open_char = '';
                        }
                    } else {
                        $open = true;
                        $open_char = $current;
                    }
                }
            }
            if (($current == ';' && !$open) || ($i == $end - 1)) {
                $query_split[] = substr($queries, $start, ($i - $start + 1));
                $start = $i + 1;
            }
        }

        return $query_split;
    }

    /**
     * @param type $table
     * @param type $extra
     *
     * @return \class
     */
    public static function findAll($table, $extra = null)
    {
        $qry = 'SELECT * FROM '.$table;
        if (is_numeric($extra)) {
            $extra = array_slice(func_get_args(), 1);
        }
        if (is_array($extra)) {
            echo $qry .= ' WHERE `id` IN ('.implode(', ', $extra).')';
        } elseif (is_string($extra)) {
            $args = array_slice(func_get_args(), 2);
            $qry .= ' '.$extra;
        }
        $results = self::getInstance()->fetchAssoc($qry);
        $models = [];
        $class = ucfirst(plural($table));
        foreach ($results as $result) {
            $models[] = new $class($result);
        }

        return $models;
    }

    /**
     * @return type
     */
    public function test()
    {
        if (empty($this->test)) {
            $this->test = 'Hello world:'.__LINE__;
        }

        return $this->test;
    }
}
