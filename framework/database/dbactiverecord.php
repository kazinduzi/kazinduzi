<?php defined('KAZINDUZI_PATH') || exit('No direct script access allowed');
/**
 * Kazinduzi Framework (http://framework.kazinduzi.com/)
 *
 * @author    Emmanuel Ndayiragije <endayiragije@gmail.com>
 * @link      http://kazinduzi.com
 * @copyright Copyright (c) 2010-2013 Kazinduzi. (http://www.kazinduzi.com)
 * @license   http://kazinduzi.com/page/license MIT License
 * @package   Kazinduzi
 */

class DbActiveRecord 
{
    /**
     * CONSTANTS
     */
    const GROUP_CRITERIA  = ' GROUP BY ';
    const ORDER_CRITERIA  = ' ORDER BY ';
    const HAVING_CRITERIA = ' HAVING ';
    const LIMIT_CRITERIA  = ' LIMIT ';
    const OFFSET_CRITERIA = ' OFFSET ';

    /**
     * @var Database Object for the loaded driver
     */
    protected static $db;

    /**
     * @var DbActiveRecord object
     */
    protected static $instance;

    /**
     * Create a singleton object of DbActiveRecord
     * @return type
     */
    public static function getSingleton() 
    {
        if (empty(self::$instance)) {
	    self::$instance = new static;
	}
        return self::$instance;
    }

    /**
     * @var string  The query type.
     */
    protected $type = '';

    /**
     * @var object  The select element.
     */
    protected $select = null;

    /**
     * @var     object	The delete element.
     */
    protected $delete = null;

    /**
     * @var	object	The update element.
     */
    protected $update = null;

    /**
     * @var	object	The insert element.
     */
    protected $insert = null;

    /**
     * @var	object	The from element.
     */
    protected $from = null;

    /**
     * @var	object	The joins elements.
     */
    protected $join,
	$innerjoin,
	$leftjoin,
	$rightjoin,
	$crossjoin,
	$naturaljoin = null;

    /**
     * @var object The set element.
     */
    protected $set = null;

    /**
     * @var object The where element.
     */
    protected $where = null;

    /**
     * @var object The group by element.
     */
    protected $group = null;

    /**
     * @var object The having element.
     */
    protected $having = null;

    /**
     * @var	object	The order element.
     */
    protected $order = null;

    /**
     * @var type
     */
    protected $limit, $offset = null;

    /**
     *
     */
    protected $union = null;

    /**
     * Initializes this model.
     * This method is invoked when an AR instance is newly created and has
     * its {@link scenario} set.
     * You may override this method to provide code that is needed to initialize the model (e.g. setting
     * initial property values.)
     * @since 1.0.8
     */
    public function init()
    {
	
    }

    /**
     *
     * @param type $clause
     * @return DbActiveRecord
     */
    public function clear($clause = null) 
    {
        switch ($clause) {
            case 'select':
		$this->select = null;
		$this->type = null;
		break;
            case 'delete':
		$this->delete = null;
		$this->type = null;
		break;
            case 'update':
		$this->update = null;
		$this->type = null;
		break;
            case 'insert':
		$this->insert = null;
		$this->type = null;
		break;
            case 'from':
		$this->from = null;
		break;
            case 'join':
		$this->join = null;
		break;
            case 'set':
		$this->set = null;
		break;
            case 'where':
		$this->where = null;
		break;
            case 'group':
		$this->group = null;
		break;

            case 'having':
		$this->having = null;
		break;
            case 'order':
		$this->order = null;
		break;
            default:
		$this->type = null;
		$this->select = null;
		$this->delete = null;
		$this->update = null;
		$this->insert = null;
		$this->from = null;
		$this->join = null;
		$this->set = null;
		$this->where = null;
		$this->group = null;
		$this->having = null;
		$this->order = null;
		$this->join = null;
		$this->innerjoin = null;
		$this->leftjoin = null;
		$this->rightjoin = null;
		$this->crossjoin = null;
		$this->naturaljoin = null;
		break;
        }
        return $this;
    }


    /**
     *
     * @static type $AR object
     */
    public function reset() 
    {
        $reflect = new ReflectionClass(new self);
        $props = $reflect->getProperties(ReflectionProperty::IS_PROTECTED);
        foreach($props as $prop) {
            if (isset($this->{$prop->getName()})) {
                $this->{$prop->getName()} = null;
            }
        }
        return $this;
    }

    /**
     * @return type
     */
    public function getDbo() 
    {
        if (self::$db !== null) {
            return self::$db;
        } else {
            return self::$db = Database::getInstance();
        }
    }

    /**
     *
     * @return type
     */
    public function getDbox()
    {
        return $this->db = Database::getInstance();
    }

    /**
     *
     * @param type $columns
     * @return \DbActiveRecord
     */
    public function select($columns = '*') 
    {
        $this->type = 'select';
        if (is_null($this->select)) {
            $this->select = new DbActiveRecordElement('SELECT', $columns);
        } else {
            $this->select->append($columns);
        }
        return $this;
    }

    /**
     *
     * @param type $columns
     * @return \DbActiveRecord
     */
    public function select_distinct($columns='*') 
    {
        $this->type = 'select';
        if (is_null($this->select)) {
            $this->select = new DbActiveRecordElement('SELECT DISTINCT', $columns);
        } else {
            $this->select->append($columns);
        }
        return $this;
    }

    /***
     *
     */
    public function select_avg($column) 
    {
        $this->type = 'select';
        $this->select = 'SELECT AVG(' . $column . ') as ' . $column;
        return $this;
    }

    /**
     * @param type $column
     * @return DbActiveRecord
     */
    public function select_min($column) 
    {
        $this->type = 'select';
        $this->select = 'SELECT MIN(' . $column . ') as ' . $column;
        return $this;
    }

    /**
     * @param type $column
     * @return DbActiveRecord
     */
    public function select_max($column) 
    {
        $this->type = 'select';
        $this->select = 'SELECT MAX(' . $column . ') as ' . $column;
        return $this;
    }

    /**
     * @param type $column
     * @return DbActiveRecord
     */
    public function select_sum($column) 
    {
        $this->type = 'select';
        $this->select = 'SELECT SUM(' . $column . ') as ' . $column;
        return $this;
    }

    /**
     * @param	mixed	A string or array of table names.
     *
     * @return	DbActiveRecord	Returns this object to allow chaining.
     */
    public function from($tables) 
    {
        if (is_null($this->from)) {
            $this->from = new DbActiveRecordElement('FROM', $tables);
        } else {
            $this->from->append($tables);
        }
        return $this;
    }


    /**
     * Sets the WHERE part of the query.
     *
     * The method requires a $conditions parameter, and optionally a $params parameter
     * specifying the values to be bound to the query.
     *
     * The $conditions parameter should be either a string (e.g. 'id=1') or an array.
     * If the latter, it must be of the format <code>array(operator, operand1, operand2, ...)</code>,
     * where the operator can be one of the followings, and the possible operands depend on the corresponding
     * operator:
     * <ul>
     * <li><code>and</code>: the operands should be concatenated together using AND.
     * For example,
     * array('and', 'id=1', 'id=2') will generate 'id=1 AND id=2'.
     *
     * If an operand is an array, it will be converted into a string using the same rules described here.
     * For example,
     * array('and', 'type=1', array('or', 'id=1', 'id=2')) will generate 'type=1 AND (id=1 OR id=2)'.
     *
     * The method will NOT do any quoting or escaping.</li>
     * <li><code>or</code>: similar as the <code>and</code> operator except that the operands are concatenated using OR.</li>
     * <li><code>in</code>: operand 1 should be a column or DB expression, and operand 2 be an array representing
     * the range of the values that the column or DB expression should be in.
     * For example,
     * array('in', 'id', array(1,2,3)) will generate 'id IN (1,2,3)'.
     *
     * The method will properly quote the column name and escape values in the range.</li>
     * <li><code>not in</code>: similar as the <code>in</code> operator except that IN is replaced with NOT IN in the generated condition.</li>
     * <li><code>like</code>: operand 1 should be a column or DB expression, and operand 2 be a string or an array representing
     * the range of the values that the column or DB expression should be like.
     * For example,
     * array('like', 'name', 'tester') will generate "name LIKE '%tester%'".
     * When the value range is given as an array, multiple LIKE predicates will be generated and concatenated using AND.
     * For example,
     * array('like', 'name', array('test', 'sample')) will generate "name LIKE '%test%' AND name LIKE '%sample%'".
     * The method will properly quote the column name and escape values in the range.</li>
     * <li><code>not like</code>: similar as the <code>like</code> operator except that LIKE is replaced with NOT LIKE in the generated condition.</li>
     * <li><code>or like</code>: similar as the <code>like</code> operator except that OR is used to concatenated the LIKE predicates.</li>
     * <li><code>or not like</code>: similar as the <code>not like</code> operator except that OR is used to concatenated the NOT LIKE predicates.</li>
     * </ul>
     *
     * @param mixed $conditions the conditions that should be put in the WHERE part.
     * @param array $params the parameters (name=>value) to be bound to the query
     * @return DbActiveRecord object

     */
    public function where($conditions) 
    {
        /*
        echo $this->processConditions(array('and', 'id=1', 'id=2'));
        echo $this->processConditions(array('and', 'type=1', array('or', 'id=1', 'id=2')));
        echo $this->processConditions(array('in', 'id', array(1,2,3)));
        echo $this->processConditions(array('like', 'name', 'tester'));
        echo $this->processConditions(array('like', 'name', array('test', 'sample')));
         */
        if (is_null($this->where)) {
            $this->where = new DbActiveRecordElement('WHERE', $this->proceedConditions($conditions));
        } else {
            $this->where->append($conditions);
        }
        return $this;
    }

    /**
     * 
     * @param string $table
     * @param string $conditions
     * @return $this
     */
    public function join($table, $conditions) 
    {
        return $this->joinInternal('join', $table, $conditions);
    }

    /**
     * LEFT-JOIN
     * 
     * @param string $table
     * @param string $conditions
     * @return $this
     */
    public function leftJoin($table, $conditions) 
    {
        return $this->joinInternal('left join', $table, $conditions);
    }

    /**
     * Appends a RIGHT OUTER JOIN part to the query.
     * @param string $table the table to be joined.
     * Table name can contain schema prefix (e.g. 'public.tbl_user') and/or table alias (e.g. 'tbl_user u').
     * The method will automatically quote the table name unless it contains some parenthesis
     * (which means the table is given as a sub-query or DB expression).
     * @param mixed $conditions the join condition that should appear in the ON part.
     * Please refer to {@link where} on how to specify conditions.
     * @param array $params the parameters (name=>value) to be bound to the query
     */
    public function rightJoin($table, $conditions) 
    {
        return $this->joinInternal('right join', $table, $conditions);
    }

    /**
     * CROSS-JOIN
     * 
     * @param string $table
     * @param string $conditions
     * @return $this
     */
    public function crossjoin($table, $conditions) 
    {
        return $this->joinInternal('cross join', $table, $conditions);
    }

    /**
     * NATURAL-JOIN
     * 
     * @param string $table
     * @param string $conditions
     * @return $this
     */
    public function naturaljoin($table, $conditions) 
    {
        return $this->joinInternal('natural join', $table, $conditions);
    }

    /**
     * INNER-JOIN
     * 
     * @param string $table
     * @param string $conditions
     * @return $this
     */
    public function innerjoin($table, $conditions) 
    {
        return $this->joinInternal('inner join', $table, $conditions);
    }

    /**
     *
     *
     */
    public function group($columns)
    {
        if (!is_array($columns)) {
            $columns = preg_split('/\s*,\s*/',trim($columns), -1, PREG_SPLIT_NO_EMPTY);
        }
        foreach ($columns as $i => $column)
        {
            if (is_object($column)) {
                $columns[$i] = (string)$column;
            }
            else if (strpos($column,'(') === false) {
                $columns[$i] = $this->getDbo()->quoteColumn($column);
            }
        }
        if (is_null($this->group)) {
            $this->group = new DbActiveRecordElement(self::GROUP_CRITERIA, $columns);
        }
        else {
            $this->group->append($conditions);
        }
        return $this;
    }

    /**
     * Sets the HAVING part of the query.
     * @param mixed $conditions the conditions to be put after HAVING.
     * Please refer to {@link where} on how to specify conditions.
     * @param array $params the parameters (name=>value) to be bound to the query
     * @return DbActiveRecord object
     */
    public function having($conditions) {
        if (is_null($this->having)) {
            $this->having = new DbActiveRecordElement(self::HAVING_CRITERIA, $this->proceedConditions($conditions));
        } else {
            $this->having->append($conditions);
        }
        return $this;
    }


    /**
     * Sets the ORDER BY part of the query.
     * @param mixed $columns the columns (and the directions) to be ordered by.
     * Columns can be specified in either a string (e.g. "id ASC, name DESC") or an array (e.g. array('id ASC', 'name DESC')).
     * The method will automatically quote the column names unless a column contains some parenthesis
     * (which means the column contains a DB expression).
     * @return DbActiveRecord object
     */
    public function order($columns, $direction = 'DESC') {
        $direction = ' ' . strtoupper($direction);
        if (is_string($columns) && strpos($columns,'(') !== false) {
            $this->order = self::ORDER_CRITERIA . $columns.$direction;
        } else {
            if(!is_array($columns)) {
                $columns = preg_split('/\s*,\s*/',trim($columns), -1, PREG_SPLIT_NO_EMPTY);
            }
            foreach($columns as $i => $column) {
                if (is_object($column)) {
                    $columns[$i] = (string)$column;
                }
                else if(strpos($column,'(') === false) {
                    if (preg_match('/^(.*?)\s+(asc|desc)$/i', $column, $matches)) {
                        $columns[$i] = $this->getDbo()->quoteColumn($matches[1]).' '.strtoupper($matches[2]);
                    } else {
                        $columns[$i] = $this->getDbo()->quoteColumn($column);
                    }
                }
            }
            $this->order = self::ORDER_CRITERIA . implode(', ',$columns) . $direction;;
        }
        return $this;
    }

    /**
     * Sets the LIMIT part of the query.
     * @param integer $limit the limit
     * @param integer $offset the offset
     * @return DbActiveRecord object
     */
    public function limit($limit, $offset = null) {
        if ($offset !== null) {
            $this->offset = (int)$offset;
            $this->limit = $this->_limit((int)$limit, $this->offset);
        } else {
            $this->limit = $this->_limit((int)$limit);
        }
        return $this;
    }

    /**
     * Appends a SQL statement using UNION operator.
     * @param string $sql the SQL statement to be appended using UNION
     * @return DbActiveRecord object
     */
    public function union($sql) {
        if (is_string($sql)) {
            $this->union = ' UNION (\n'.trim($sql).')';
        }
        if (is_array($sql)) {
            $sql = array_map('trim', $sql);
            $this->union = ' UNION (\n'. implode('\n) UNION (\n', $sql) . '\n)';
        }
        return $this;
    }


    /**
     * Creates and executes an UPDATE SQL statement.
     * The method will properly escape the column names and bind the values to be updated.
     * @param string $table the table to be updated.
     * @param array $columns the column data (name=>value) to be updated.
     * @param mixed $conditions the conditions that will be put in the WHERE part. Please
     * refer to {@link where} on how to specify conditions.
     * @param array $params the parameters to be bound to the query.
     * @return integer number of rows affected by the execution.
     */
    public function update($table, $columns, $conditions='', $params=array()) {
        $lines = array();
        foreach($columns as $name => $value) {
            $lines[] = $this->getDbo()->quoteColumn($name) . '=' . $this->getDbo()->quote($value);
        }
        $sql = 'UPDATE ' . $this->getDbo()->quoteTable($table).' SET ' . implode(', ', $lines);
        if ('' != ($where = $this->proceedConditions($conditions))) {
            $sql .= ' WHERE '.$where;
        }
        $this->autocommit(false);
        try {
            $this->getDbo()->setQuery($sql)->execute($params);	    
            $this->commit();
        } catch(Exception $e) {
            $this->rollback();
            print_r($e);
        }

    }

    /**
     * @param type $table
     * @param array $data
     * @return int inserted_id
     */
    public function insert($table, $data) {
        $params = array();
        $names = array();
        foreach ($data as $name => $value) {
            $names[] = $this->quoteColumn($name);
            $params[] = $value;
        }
        $params = array_map(array($this->getDbo(), 'quote'), $params);
        $sql = 'INSERT INTO ' . $this->quoteTable($table) . '(' . implode(', ', $names) . ') VALUES (' . implode(', ', array_values($params)) . ')';
        $this->autocommit(false);
        try {
            $this->setQuery($sql)->execute();
            $inserted_id = $this->insert_id();
            $this->commit();
            return $inserted_id;
        }
        catch (Exception $e) {
            $this->rollback();
            print_r($e);
        }
    }

    /**
     * @param type $table
     * @param array $data
     * @return int inserted_id
     */
    public function replace($table, $data) {
        $params = array();
        $names = array();
        foreach ($data as $name => $value) {
            $names[] = $this->quoteColumn($name);
            $params[] = $value;
        }
        $params = array_map(array($this->getDbo(), 'quote'), $params);
        $sql = 'REPLACE INTO ' . $this->quoteTable($table) . '(' . implode(', ', $names) . ') VALUES (' . implode(', ', array_values($params)) . ')';
        $this->autocommit(false);
        try {
            $this->setQuery($sql)->execute();
            $inserted_id = $this->insert_id();
            $this->commit();
            return $inserted_id;
        }
        catch (Exception $e) {
            $this->rollback();
            print_r($e);
        }
    }


    /**
     * Creates and executes a DELETE SQL statement.
     * @param string $table the table where the data will be deleted from.
     * @param mixed $conditions the conditions that will be put in the WHERE part. Please
     * refer to {@link where} on how to specify conditions.
     * @param array $params the parameters to be bound to the query.
     * @return integer number of rows affected by the execution.
     */
    public function delete($table, $conditions = '', $params=array()) {
        $sql = 'DELETE FROM ' . $this->getDbo()->quoteTable($table);
        if (($where = $this->proceedConditions($conditions)) != '') {
            $sql .= ' WHERE ' . $where;
        }
        $this->autocommit(false);
        try{
            $this->getDbo()->setQuery($sql)->execute($params);
            $affected = $this->getDbo()->affected_rows();
            $this->commit();
            return $affected;
        } catch (Exception $e) {
            $this->rollback();
            print_r($e);
        }
    }

    /**
     * Builds and executes a SQL statement for creating a new DB table.
     *
     * The columns in the new  table should be specified as name-definition pairs (e.g. 'name'=>'string'),
     * where name stands for a column name which will be properly quoted by the method, and definition
     * stands for the column type which can contain an abstract DB type.
     * The {@link getColumnType} method will be invoked to convert any abstract type into a physical one.
     *
     * If a column is specified with definition only (e.g. 'PRIMARY KEY (name, type)'), it will be directly
     * inserted into the generated SQL.
     *
     * @param string $table the name of the table to be created. The name will be properly quoted by the method.
     * @param array $columns the columns (name=>definition) in the new table.
     * @param string $options additional SQL fragment that will be appended to the generated SQL.
     * @return integer number of rows affected by the execution.
     */
    public function createTable($table, $columns, $options = null) {
        static $dbo;
        $dbo = $this->getDbo();
        $qry = $dbo->createTableQuery($table, $columns, $options = null);
        return $this->getDbo()->setQuery($qry)->execute();
    }

    /**
     *
     * @staticvar type $dbo
     * @param type $table
     * @param type $columns
     * @param null $options
     * @return type
     */
    public function createTemporaryTable($table, $columns, $options = null) {
        static $dbo;
        $dbo = $this->getDbo();
        $qry = $dbo->createTableQuery($table, $columns, $options = null, $temp=true);
        return $this->getDbo()->setQuery($qry)->execute();
    }

    /**
     * Builds and executes a SQL statement for renaming a DB table.
     * @param string $table the table to be renamed. The name will be properly quoted by the method.
     * @param string $newName the new table name. The name will be properly quoted by the method.
     * @return integer number of rows affected by the execution.
     */
    public function renameTable($table, $newName) {
        static $dbo;
        $dbo = $this->getDbo();
        $dbo->setQuery($dbo->renameTableQuery($table, $newName))->execute();
        return $dbo->clear();
    }

    /**
     * Builds and executes a SQL statement for dropping a DB table.
     * @param string $table the table to be dropped. The name will be properly quoted by the method.
     * @return integer number of rows affected by the execution.
     */
    public function dropTable($table) {
        static $dbo;
        $dbo = $this->getDbo();
        $dbo->setQuery($dbo->dropTableQuery($table))->execute();
        return $dbo->clear();
    }

    /**
     * Builds and executes a SQL statement for truncating a DB table.
     * @param string $table the table to be truncated. The name will be properly quoted by the method.
     * @return integer number of rows affected by the execution.
     */
    public function truncateTable($table) {
        static $dbo;
        $dbo = $this->getDbo();
        $nr = $dbo->setQuery($dbo->truncateTableQuery($table))->execute();
        if(strncasecmp($this->getDbo()->name, 'sqlite', 6) === 0) {
            $qry = "UPDATE sqlite_sequence SET seq='$value' WHERE name='{$table->name}'";
            $dbo->setQuery($qry)->execute();
        }
        // Clear query
        $dbo->clear();
        return $nr;
    }

    /**
     * Builds and executes a SQL statement for adding a new DB column.
     * @param string $table the table that the new column will be added to. The table name will be properly quoted by the method.
     * @param string $column the name of the new column. The name will be properly quoted by the method.
     * @param string $type the column type. The {@link getColumnType} method will be invoked to convert abstract column type (if any)
     * into the physical one. Anything that is not recognized as abstract type will be kept in the generated SQL.
     * For example, 'string' will be turned into 'varchar(255)', while 'string not null' will become 'varchar(255) not null'.
     * @return integer number of rows affected by the execution.
     */
    public function addColumn($table, $column, $type) {
        $dbo = $this->getDbo();
        $dbo->setQuery($dbo->getSchema()->addColumn($table, $column, $type))->execute();
        return $dbo->clear();
    }

    /**
     * Builds and executes a SQL statement for dropping a DB column.
     * @param string $table the table whose column is to be dropped. The name will be properly quoted by the method.
     * @param string $column the name of the column to be dropped. The name will be properly quoted by the method.
     * @return integer number of rows affected by the execution.
     */
    public function dropColumn($table, $column) {
        $dbo = $this->getDbo();
        return $dbo->setText($dbo->getSchema()->dropColumn($table, $column))->execute();
    }

    /**
     * Builds and executes a SQL statement for renaming a column.
     * @param string $table the table whose column is to be renamed. The name will be properly quoted by the method.
     * @param string $name the old name of the column. The name will be properly quoted by the method.
     * @param string $newName the new name of the column. The name will be properly quoted by the method.
     * @return integer number of rows affected by the execution.
     */
    public function renameColumn($table, $name, $newName) {
        $dbo = $this->getDbo();
        $this->setQuery($dbo->renameColumn($table, $name, $newName))->execute();
        return $this->clear();
    }

    /**
     * Builds and executes a SQL statement for changing the definition of a column.
     * @param string $table the table whose column is to be changed. The table name will be properly quoted by the method.
     * @param string $column the name of the column to be changed. The name will be properly quoted by the method.
     * @param string $type the new column type. The {@link getColumnType} method will be invoked to convert abstract column type (if any)
     * into the physical one. Anything that is not recognized as abstract type will be kept in the generated SQL.
     * For example, 'string' will be turned into 'varchar(255)', while 'string not null' will become 'varchar(255) not null'.
     * @return integer number of rows affected by the execution.
     */
    public function alterColumn($table, $column, $type) {
        $dbo = $this->getDbo();
        $dbo->setQuery($dbo->getSchema()->alterColumn($table, $name, $newName))->execute();
        return $this->clear();
    }

    /**
     *
     * @param type $limit
     * @param type $offset
     */
    private function _limit($limit, $offset=null) {
        if(in_array(strtolower($this->getDbo()->name), array('mysql','sqlite')))
        {
            if ($offset == null) $offset = '';
            else $offset .= ', ';
            return self::LIMIT_CRITERIA.$offset.$limit;
        }
        //
        if(in_array(strtolower($this->getDbo()->name), array('mysqli','pg','postgre')))
        {
            $criteria = self::LIMIT_CRITERIA.$limit;
            if((int)$offset > 0) {
                $criteria .= self::OFFSET_CRITERIA.$offset;
            }
            return $criteria;
        }
    }

    /**
     * Generates the condition string that will be put in the WHERE part
     * @param mixed $conditions the conditions that will be put in the WHERE part.
     * @return string the condition string to put in the WHERE part
     */
    private function proceedConditions($conditions) 
    {
        if (!is_array($conditions)) {
	    return $conditions;
	} elseif ($conditions === array()) {
	    return '';
	}
        $n = count($conditions);
        $operator = strtoupper($conditions[0]);
        if ($operator === 'OR' || $operator === 'AND') {
            $parts = array();
            for ($i = 1; $i < $n; ++$i) {
                $condition = $this->proceedConditions($conditions[$i]);
                if ($condition !== '') {
                    $parts[] = '(' . $condition . ')';
                }
            }
            return $parts === array() ? '' : implode(' ' . $operator . ' ', $parts);
        }

        if (!isset($conditions[1],$conditions[2])) {
	    return '';
	}
        $column = $conditions[1];
        if (strpos($column,'(') === false) {
            $column = '`' . $column . '`';
        }
        $values = $conditions[2];
        if (!is_array($values)) {
            $values = array($values);
        }

        if ($operator === 'IN' || $operator === 'NOT IN') {
            if ($values === array()) {
		return $operator === 'IN' ? '0=1' : '';
	    }
            foreach($values as $i=>$value) {
                if (is_string($value)) {
                    $values[$i] = $this->getDbo()->quote($value);
                } else {
                    $values[$i] = (string)$value;
                }
            }
            return $column.' '.$operator.' ('.implode(', ',$values).')';
        }

        if ($operator === 'LIKE' || $operator === 'NOT LIKE' || $operator === 'OR LIKE' || $operator === 'OR NOT LIKE') {
            if ($values === array()) {
                return $operator === 'LIKE' || $operator === 'OR LIKE' ? '0=1' : '';
            }
            if ($operator === 'LIKE' || $operator === 'NOT LIKE') {
                $andor = ' AND ';
            } else {
                $andor = ' OR ';
                $operator = $operator === 'OR LIKE' ? 'LIKE' : 'NOT LIKE';
            }
            $expressions = array();
            foreach ($values as $value) {
                $expressions[] = $column . ' ' . $operator . ' ' . $this->getDbo()->quote('%' . $value . '%');
            }
            return implode($andor,$expressions);
        }
        throw new \Exception('Unknown operator ' . $operator . '.');
    }

    /**
     * Appends an JOIN part to the query.
     * @param string $type the join type ('join', 'left join', 'right join', 'cross join', 'natural join')
     * @param string $table the table to be joined.
     * Table name can contain schema prefix (e.g. 'public.tbl_user') and/or table alias (e.g. 'tbl_user u').
     * The method will automatically quote the table name unless it contains some parenthesis
     * (which means the table is given as a sub-query or DB expression).
     * @param mixed $conditions the join condition that should appear in the ON part.
     * Please refer to {@link where} on how to specify conditions.
     * @param array $params the parameters (name=>value) to be bound to the query
     * @return DbActiveRecord object itself
     */
    private function joinInternal($type, $table, $conditions = '') 
    {
        if (strpos($table,'(') === false) {
            if (preg_match('/^(.*?)\s+(.*)$/', $table, $matches))  {
                $table = $matches[1].' '.$matches[2];
            }
        }
        $conditions = $this->proceedConditions($conditions);
        if ($conditions != '') {
            $conditions = ' ON '.$conditions;
        }
        if (isset($this->join) && is_string($this->join)) {
            $this->join = array($this->join);
        }
        $this->join[] = ' ' . strtoupper($type) . ' ' . $table . $conditions;
        return $this;
    }

    /**
     * Magic function to convert the query object to a string.
     * @return	string	The completed query.
     */
    public function __toString() 
    {
        $query = '';
        switch ($this->type) {
            case 'select':
                    $query .= (string)$this->select;
                    $query .= (string)$this->from;
                    if ($this->join) {
                        // special case for joins
                        foreach ($this->join as $join) {
                            $query .= (string)$join;
                        }
                    }
                    if ($this->where) {
                         $query .= (string)$this->where;
                    }
                    if ($this->group) {
                         $query .= (string)$this->group;
                    }
                    if ($this->having) {
                         $query .= (string)$this->having;
                    }
                    if ($this->order) {
                         $query .= (string)$this->order;
                    }
                    if ($this->limit) {
                         $query .= (string)$this->limit;
                    }
                    if ($this->union) {
                         $query .= (string)$this->union;
                    }
                    break;

            case 'delete':
                    $query .= (string)$this->delete;
                    $query .= (string)$this->from;
                    if ($this->join) {
                        // special case for joins
                        foreach ($this->join as $join) {
                            $query .= (string)$join;
                        }
                    }
                    if($this->where) {
                        $query .= (string)$this->where;
                    }
                    break;
            case 'update':
                    $query .= (string)$this->update;
                    $query .= (string)$this->set;
                    if($this->where) {
                        $query .= (string)$this->where;
                    }
                    break;
            case 'insert':
                    $query .= (string)$this->insert;
                    $query .= (string)$this->set;
                    if($this->where) {
                        $query .= (string)$this->where;
                    }
                    break;
        }
        //
        return $this->sql = $query;
     }

     function test() {
         echo get_class($this);
     }

}