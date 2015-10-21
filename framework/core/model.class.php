<?php

defined('KAZINDUZI_PATH') || exit('No direct script access allowed');
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
 * Model holds an array of values for easy access
 * @package kazinduzi
 */
abstract class Model /* extends DbActiveRecord */ implements IteratorAggregate
{

    /**
     * Base Model class
     * The data from the database
     */
    const BELONGS_TO = '_belongs_to';
    const HAS_MANY = '_has_many';
    const HAS_ONE = '_has_one';
    const MANY_MANY = '_many_many';

    /**
     * Active Record Object
     * @var DbActiveRecord
     */
    private $ar;

    /**
     * Oject of the current model
     * @var array
     */
    public $values = array();

    /**
     * Table name for the current model
     * @var string
     */
    protected $table = null;

    /**
     * Table columns
     * @var array
     */
    protected $tableColumns;

    /**
     * Auto-update columns for updates
     * @var string
     */
    protected $_updated_column = null;

    /**
     * Auto-update columns for creation
     * @var string
     */
    protected $_created_column = null;

    /**
     * Model name
     * @var string
     */
    protected $modelName;

    /**
     * Current object
     * @var array
     */
    //protected $_object = array();

    /**
     * @var array
     */
    protected $_changed = array();

    /**
     * Table primary key
     * @var string
     */
    protected $pk = 'id';

    /**
     * Primary key value
     * @var mixed
     */
    protected $pkValue;

    /**
     * Foreign key suffix
     * @var string
     */
    protected $foreign_key_suffix = '_id';

    /**
     * The id for the current model
     * @var mixed
     */
    protected $id = false;

    /**
     * Data to be loaded into the model from a database call cast
     * @var array
     */
    protected $_cast_data = array();

    /**
     * @var bool
     */
    protected $_loaded = false;

    /**
     * @var bool
     */
    protected $_saved = false;

    /**
     * @var bool
     */
    protected $_valid = false;

    /**
     * Stores column information for ORM models
     * @var array
     */
    protected static $_column_cache = array();

    /**
     * Hold all loaded models
     * @var array
     */
    private static $models = array();

    /**
     * Database Object for current connection
     * @var object
     */
    protected static $db;

    /**
     * Creates and returns a new model.
     * @chainable
     * @param   string  $modelname  Model name
     * @param   mixed   $id     Parameter for findX()
     * @return  Model
     */
    public static function forgery($modelname, $id = null)
    {
	$modelname = ucfirst($modelname);
	if (is_array($id)) {
	    $col = key($id);
	    self::$db->clear();
	    self::$db->select('*')
		    ->from(self::$db->quoteTable(strtolower($modelname)))
		    ->where(self::$db->quoteColumn($col) . "=" . self::$db->quote($id[$col]))
		    ->buildQuery();
	    $row = self::$db->fetchAssocRow();
	    return new $modelname($row);
	}
	return new $modelname($id);
    }

    /**
     * Related relations to the model
     * @var array
     */
    public $relationships = array();

    /**
     * 'Belongs_to' relations
     * @var array
     */
    public $belongsTo = array();

    /**
     * 'Has_one' relations
     * @var array
     */
    public $hasOne = array();

    /**
     * 'Has_many' relations
     * public $manyMany = array('entry' => array(
     *      'model'         => 'entries',
     *      'through_key'   => 'category_id',
     *      'far_key'       => 'entry_id',
     *      'through'       => 'entry_category' )
     * );
     * @var array
     */
    public $hasMany = array();

    /**
     * 'Many_many' relations
     * @var array
     */
    public $manyMany = array();

    /**
     * Constructor of the model
     * @param mixed $values
     */
    public function __construct($id = null)
    {
	// Call init method to initialize the model
	$this->_init();
	$this->modelName = ucfirst(get_class($this));
	$this->ar = DbActiveRecord::getSingleton();
	if ($id !== null) {
	    if (is_array($id)) {
		$this->values = $id;
		if (isset($this->values[$this->pk])) {
		    $this->id = $this->pkValue = $this->values[$this->pk];
		}
	    } else {
		self::$db->select('*')
			->from($this->table)
			->where($this->table . '.' . $this->pk . '=' . $id)->buildQuery();
		$this->values = self::$db->fetchAssocRow();
		if (empty($this->values)) {
		    throw new Exception($this->table . ' with PK:' . $this->pk . ' = ' . $id . ' does not exist.');
		}
		$this->id = $this->pkValue = $id;
	    }
	} elseif (!empty($this->_cast_data)) {
	    $this->_load_values($this->_cast_data);
	    echo 'casting data...';
	    $this->_cast_data = array();
	}
	return null;
    }

    /**
     * Magic method __call will trigger the method from ActiveRecord, because this model is not extending Active Record.
     * It incapsulate the ActiveRecord instance within its property.
     *
     * @param string $method
     * @param mixed $arguments
     * @return mixed
     * @throws Exception
     */
    public function __call($method, $arguments)
    {
	if (method_exists($this->ar, $method)) {
	    return $this->ar->{$method}($arguments);
	} else {
	    throw new Exception('Inexistent method [' . $method . '] within DbActiveRecord');
	}
    }

    /**
     * Get the name of model
     * @link This method check if {$this->modelName} is set, otherwise get_called_class() will be returned
     * @return type string
     */
    public function getName()
    {
	return isset($this->modelName) ? $this->modelName : get_called_class();
    }

    /**
     * Model::getInstance creates a single instance of the model knowing its class name,
     * or its static model instance extending the model.
     *
     * @param string $className
     * @param array $options
     * @return \Model instance
     */
    public static function getInstance($className = null, $options = array())
    {
	if (empty($className)) {
	    $className = get_called_class();
	}
	if (isset(self::$models[$className])) {
	    return self::$models[$className];
	} else {
	    $model = self::$models[$className] = new $className($options);
	    return $model;
	}
    }

    /**
     * This static method return all the instances
     * @return \Model
     */
    public static function singletons()
    {
	return self::$models;
    }

    /**
     * This static method returns an instance of the model
     * @param string $className
     * @return \Model
     */
    public static function model($className = null)
    {
	return self::getInstance($className);
    }

    /**
     * This method return the id of the model, alias to the primary key of the DB table of the model
     * @return mixed
     */
    public function getId()
    {
	return $this->id;
    }

    /**
     * Get a clean database instance
     * @return \Database
     */
    public function getDbo()
    {
	return Database::getInstance()->clear();
    }

    /**
     * Get the instance of the ActiveRecord
     * @return \DbActiveRecord
     */
    public function getAR()
    {
	return $this->ar;
    }

    /**
     * Get the instance of the ActiveRecord
     * @return \DbActiveRecord
     */
    public function getActiveRecord()
    {
	return $this->ar;
    }

    /**
     * Get the table attached to the model
     * @return string
     */
    public function getTable()
    {
	if (!isset($this->table)) {
	    return $this->table = strtolower(plural(get_class($this)));
	}
	return $this->table;
    }

    /**
     * Finds the model knowing its id
     *
     * @param mixed $id
     * @return \Model
     */
    public function findById($id)
    {
	self::$db->clear();
	self::$db->select('*')
		->from($this->table)
		->where("id={$id}")->buildQuery();
	$this->values = self::$db->fetchAssocRow();
	if (array_key_exists($this->pk, $this->values)) {
	    $this->pkValue = $this->values[$this->pk];
	}
	return $this;
    }
    
    /**
     *
     * @param type $pri PRIMARY KEY
     * @param type $condition
     * @param type $params
     * @return type
     */
    public function findByPri($pri, $condition = null, $params = array())
    {
	self::$db->clear()
		->select('*')
		->from($this->getTable())
		->where($this->pk . '=' . $pri)
		->buildQuery();
	$this->values = self::$db->fetchAssocRow();
	if (array_key_exists($this->pk, $this->values)) {
	    $this->pkValue = $this->values[$this->pk];
	}
	return $this;
    }

    /**
     * Finds only one model
     *
     * @param string $condition
     * @param mixed $params
     * @return \Model
     */
    public function findOne($condition = '', $params = array())
    {
	self::$db->clear();
	self::$db->select('*')
		->from($this->getTable())
		->limit(1)->buildQuery();
	$this->values = self::$db->fetchAssocRow();
	if (array_key_exists($this->pk, $this->values)) {
	    $this->pkValue = $this->values[$this->pk];
	}
	return $this;
    }

    /**
     * Finds all items from model
     * @param string $condition
     * @param mixed $params
     * @return array
     */
    public function findAll($condition = '', $params = array())
    {
	self::$db->clear();
	self::$db->select('*')->from('`' . $this->getTable() . '`');
	if (empty($condition)) {
	    self::$db->buildQuery();
	} else {
	    self::$db->where($condition)->buildQuery();
	}
	if (isset($params['order_by'])) {
	    $column = $params['order_by']['column'];
	    $direction = !empty($params['order_by']['direction']) ? $params['order_by']['direction'] : 'DESC';
	    self::$db->order($column, $direction)->buildQuery();
	}
	if (isset($params['limit'])) {
	    $offset = $params['limit']['offset'];
	    $count = $params['limit']['count'];
	    self::$db->limit($count, $offset)->buildQuery();
	}
	$rows = self::$db->fetchAssocList();
	$objects = array();
	foreach ($rows as $i => $row) {
	    $row = (array) $row;
	    if (array_key_exists($this->pk, $row)) {
		$this->id = $row[$this->pk];
		$this->values = $row;
		$this->pkValue = $row[$this->pk];
	    }
	    $objects[] = new static($row);
	}
	return $objects;
    }    

    /**
     *
     * @param string $sql
     * @param array $params
     */
    public function findBySql($sql, $params = array())
    {
	self::$db->clear();
	$rows = self::$db->setQuery($sql)->fetchAssocList();
	$objects = array();
	foreach ($rows as $i => $row) {
	    $row = (array) $row;
	    if (array_key_exists($this->pk, $row)) {
		$this->values = $row;
		$this->pkValue = $row[$this->pk];
	    }
	    $objects[] = clone $this;
	}
	return $objects;
    }

    /**
     *
     * @param type $attrs
     * @param type $conds
     * @param type $params
     */
    public function findByAttr($attrs, $conds = null, $params = array())
    {
	self::$db->reset();
	if (is_string($attrs)) {
	    $attrs = (array) $attrs;
	}
	self::$db->select($attrs)->from($this->getTable());
	if (!empty($conds)) {
	    self::$db->where($conds)->buildQuery();
	} else {
	    self::$db->buildQuery();
	}
	$rows = self::$db->fetchAssocList();
	$objects = array();
	foreach ($rows as $i => $row) {
	    $row = (array) $row;
	    if (array_key_exists($this->pk, $row)) {
		$this->values = $row;
		$this->pkValue = $row[$this->pk];
	    }
	    $objects[] = clone $this;
	}
	return $objects;
    }

    /**
     *
     * @return type
     */
    public function deleteRecord()
    {
	$db = $this->getDbo()->clear();
	if (!isset($this->pkValue) || !is_numeric($this->pkValue)) {
	    return null;
	}
	$sql = "SELECT * FROM `$this->table` WHERE " . $this->pk . " = " . $this->pkValue;
	$db->setQuery($sql);
	if (count($db->fetchRow()) < 1) {
	    return null;
	}
	try {
	    $db->delete($this->table, $this->pk . '=' . $this->pkValue);
	    $db->execute("ALTER TABLE `$this->table` AUTO_INCREMENT = 1");
	    return $this->clear_model();
	} catch (Exception $e) {
	    print_r($e);
	}
    }

    /**
     *
     * @return type
     */
    public function delete()
    {
	return $this->deleteRecord();
    }

    /**
     *
     * @return mixed
     */
    public function saveRecord()
    {
	if ($this->isNew()) {
	    return $this->insertRecord();
	} else {
	    return $this->updateRecord();
	}
    }

    /**
     *
     * @return mixed
     */
    public function save()
    {
	try {
	    return $this->saveRecord();
	} catch (Exception $e) {
	    throw $e;
	}
    }

    /**
     *
     * @return type
     */
    protected function isNew()
    {
	return $this->pkValue ? false : true;
    }

    /**
     *
     * @return type
     */
    private function insertRecord()
    {
	$db = $this->getDbo()->clear();
	if (array_key_exists('created_date', $this->values) && $this->values['created_date'] != '') {
	    $this->values['created_date'] = "now()";
	}
	if (array_key_exists('site_id', $this->values) && !$this->values['site_id']) {
	    $this->values['site_id'] = 0;
	}	
	if (array_key_exists('parent_id', $this->values) && !$this->values['parent_id']) {
	    $this->values['parent_id'] = 0;
	}
	$this->id = $this->pkValue = $db->insert($this->table, $this->values);
	return new static($this->id);
    }

    /**
     *
     * @return type
     */
    private function updateRecord()
    {
	$columns = $this->tableColumns[$this->getTable()];
	$db = $this->getDbo();
	if (array_key_exists('created_date', $this->values) && $this->values['created_date'] != '') {
	    $this->values['created_date'] = "now()";
	}	
	$condition = $db->quoteColumn($this->primaryKeyField()) . " = " . $this->id;
	$db->update($this->table, $this->values, $condition);
	return new static($this->id);
    }

    /**
     *
     * @return type
     */
    public function primaryKeyField()
    {
	return $this->getPrimaryKey();
    }

    /**
     * Retrieves the PRIMARY KEY for the model
     * @return string the primary key for the current loaded model
     */
    public function getPrimaryKey()
    {
	if ($this->table) {
	    $table = $this->table;
	} else {
	    $table = get_class($this);
	}
	$fields = $this->getDbo()->tableFields($table, false/* false, to retrieve all types */);
	$result = array();
	foreach ($fields as $key => $value) {
	    $columns = array_keys($value);
	    foreach ($columns as $id => $column) {
		if (strtoupper($value[$column]->Key) == 'PRI')
		    return $column;
	    }
	}
	return null;
    }

    /**
     * Get the value for te Primary Key for this model
     * @return mixed
     */
    public function pk()
    {
	return $this->pkValue;
    }

    /**
     *
     * @return type
     */
    public static function _primaryKeyField()
    {
	$class = __CLASS__;
	if (defined($class . '::key')) {
	    return constant($class . '::key');
	}
	return 'id';
    }

    /**
     *
     * @param type $table
     * @param type $where
     * @return type
     */
    public static function count($table, $where = null)
    {
	$where = (!empty($where['WHERE'])) ? ' WHERE ' . $where['WHERE'] : '';
	if (($result = arrayFirst(Kazinduzi::db()->fetchAssoc('SELECT COUNT(id) AS count FROM `' . strtolower($table) . '`' . $where)))) {
	    return $result['count'];
	}
	return null;
    }

    /**
     *
     * @param type $table
     * @param type $args
     * @return class
     */
    public function findx($table, $args = null)
    {
	$table = strtolower($table);
	$query = 'SELECT * FROM `' . $table . '`';
	$query .= (!empty($args['WHERE'])) ? ' WHERE ' . $args['WHERE'] : '';
	$query .= (!empty($args['ORDERBY'])) ? ' ORDER BY ' . $args['ORDERBY'] : '';
	$query .= (!empty($args['LIMIT'])) ? ' LIMIT ' . $args['LIMIT'] : '';
	$this->getDbo()->setQuery($query);
	if (null !== $results = $this->getDbo()->fetchAssocList()) {
	    $models = array();
	    $class = get_called_class();
	    foreach ($results as $result) {
		$models[] = new $class($result);
	    }
	    return $models;
	}
	return null;
    }

    /**
     *
     * @param type $table
     * @param type $args
     * @return class
     */
    public static function find($table, array $args = array())
    {
	$table = strtolower($table);
	$query = 'SELECT * FROM `' . $table . '`';
	$query .=!empty($args['WHERE']) ? ' WHERE ' . $args['WHERE'] : '';
	$query .=!empty($args['ORDERBY']) ? ' ORDER BY ' . $args['ORDERBY'] : '';
	$query .=!empty($args['LIMIT']) ? ' LIMIT ' . $args['LIMIT'] : '';
	$db = \Kazinduzi::db()->clear();
	$db->setQuery($query);
	if (array() !== $results = $db->fetchAssocList()) {
	    $class = get_called_class();
	    foreach ($results as $result) {
		$models[] = new $class($result);
	    }
	    return $models;
	}
	return null;
    }

    /**
     * Unloads the current object and clears the status.
     * @chainable
     * @return Model
     */
    public function clear_model()
    {
	$cols = array_keys($this->tableColumns[$this->getTable()]);
	$values = array_combine($cols, array_fill(0, count($cols), null));
	$this->values = $this->_changed = $this->relationships = array();
	$this->_load_values($values);
	$this->pkValue = null;
	self::$db->clear();
	return $this;
    }

    /**
     * Reloads the current object from the database.
     * @chainable
     * @return Model
     */
    public function reload()
    {
	$primary_key = $this->pk();
	$this->values = /* $this->_object = */$this->_changed = $this->relationships = array();
	if ($this->_loaded) {
	    $this->clear_model();
	    self::$db->select($this->table . '.*')
		    ->from($this->table)
		    ->where($this->table . '.' . $this->pk . '=' . $primary_key)->buildQuery();
	    $this->values = self::$db->fetchAssocRow();
	    return $this;
	} else {
	    return $this->clear_model();
	}
    }

    /**
     * Set values from an array with support for one-one relationships.  This method should be used
     * for loading in post data, etc.
     * @param  array $values   Array of column => val
     * @param  array $expected Array of keys to take from $values
     * @return Model
     */
    public function values(array $values, array $expected = null)
    {
	if ($expected === null) {
	    $expected = array_keys($this->tableColumns);
	    unset($values[$this->pk]);
	}
	foreach ($expected as $key => $column) {
	    if (is_string($key)) {
		if (!array_key_exists($key, $values)) {
		    continue;
		}
		$this->{$key}->values($values[$key], $column);
	    } else {
		if (!array_key_exists($column, $values)) {
		    continue;
		}
		$this->$column = $values[$column];
	    }
	}
	return $this;
    }

    /**
     * Returns the values of this object as an array, including any related one-one
     * models that have already been loaded using with()
     * @return array
     */
    public function toArray()
    {
	$object = array();
	foreach ($this->values as $column => $value) {
	    $object[$column] = $this->__get($column);
	}
	foreach ($this->relationships as $column => $model) {
	    $object[$column] = $model->toArray();
	}
	return $object;
    }

    /**
     *
     * @param type $column
     * @return type
     */
    public function __get($column)
    {
	if (isset($this->values[$column])) {
	    return $this->values[$column];
	} elseif (array_key_exists($column, $this->values)) {
	    return $this->values[$column];
	} elseif (isset($this->relationships[$column])) {
	    return $this->relationships[$column];
	} elseif (isset($this->belongsTo[$column])) {
	    $model = $this->_related_models($column);
	    $col = $model->table . '.' . $model->pk;
	    $val = $this->values[$this->belongsTo[$column]['foreign_key']];
	    self::$db->select($model->table . '.*')
		    ->from($model->table)
		    ->where($col . '=' . $val)->buildQuery();
	    $model->values = self::$db->fetchAssocRow();
	    $model->_object = self::$db->fetchAssocRow();
	    return $this->relationships[$column] = $model;
	} elseif (isset($this->hasOne[$column])) {
	    $model = $this->_related_models($column);
	    $col = $model->table . '.' . $this->hasOne[$column]['foreign_key'];
	    $val = $this->pk();
	    self::$db->select($model->table . '.*')->from($model->table)
		    ->where($col . '=' . $val)->buildQuery();
	    $model->values = self::$db->fetchAssocRow();
	    $model->_object = self::$db->fetchAssocRow();
	    return $this->relationships[$column] = $model;
	} elseif (isset($this->hasMany[$column])) {
	    $model = self::forgery($this->hasMany[$column]['model']);
	    if (isset($this->hasMany[$column]['through'])) {
		$through = $this->hasMany[$column]['through'];
		$join_col1 = $through . '.' . $this->hasMany[$column]['far_key'];
		$join_col2 = $model->table . '.' . $model->pk;
		$col = $through . '.' . $this->hasMany[$column]['foreign_key'];
		$val = $this->pk();
		$col1 = $model->table . '.' . $this->hasMany[$column]['foreign_key'];
		$val1 = $this->pk();
		self::$db->select($model->table . '.*')
			->from($model->table)
			->innerjoin($through, $join_col1 . '=' . $join_col2)
			->where($col . '=' . $val . ' AND ' . $col1 . '=' . $val1)
			->buildQuery();
	    } else {
		$col = $model->table . '.' . $this->hasMany[$column]['foreign_key'];
		$val = $this->pk();
		self::$db->select($model->table . '.*')
			->from($model->table)
			->where($col . '=' . $val)
			->buildQuery();
	    }

	    $rows = self::$db->fetchAssocList();
	    if (count($rows) === 1) {
		$model->values = $rows[0];
		$model->_object = $rows[0];
		return array($model);
	    } elseif (count($rows) > 1) {
		$model_array = array();
		foreach ($rows as $model_data) {
		    $m = clone $model;
		    $m->values = $model_data;
		    $model_array[] = $m;
		}
		return $model_array;
	    }
	} elseif (isset($this->manyMany[$column])) {
	    $model = self::forgery($this->manyMany[$column]['model']);
	    if (isset($this->manyMany[$column]['through'])) {
		$through = $this->manyMany[$column]['through'];
		$join_col1 = $through . '.' . $this->manyMany[$column]['far_key'];
		$join_col2 = $model->table . '.' . $model->pk;
		$col = $through . '.' . $this->manyMany[$column]['through_key'];
		$val = $this->pk();
		self::$db->select($model->table . '.*')
			->from($model->table)
			->innerjoin($through, '(' . $join_col1 . '=' . $join_col2 . ' AND ' . $col . '=' . $val . ')')
			->buildQuery();
		$rows = self::$db->fetchAssocList();
		if (count($rows) === 1) {
		    $model->values = $rows[0];
		    return array($model);
		} elseif (count($rows) > 1) {
		    $array = array();
		    foreach ($rows as $data) {
			$mdl = clone $model;
			$mdl->values = $data;
			$array[] = $mdl;
		    }
		    return $array;
		}
	    } else {
		throw new \Exception('Unkown throw table for many_to_many relation ' . $this->manyMany[$column]['model']);
	    }
	} else {
	    $class = get_class($this);
	    throw new \Exception("The {$column} property does not exist in the {$class}");
	}
    }

    /**
     * Handles setting of column
     * @param  string $column Column name
     * @param  mixed  $value  Column value
     * @return void
     */
    public function __set($column, $value)
    {
	if (!isset($this->modelName)) {
	    $this->_cast_data[$column] = $value;
	} else {
	    if (isset($this->belongsTo[$column])) {
		$this->relationships[$column] = $value;
		$this->values[$this->belongsTo[$column]['foreign_key']] = $value->pk();
	    } else {
		$this->values[$column] = $value;
	    }
	}
    }

    /**
     *
     * @param type $name
     * @return type
     */
    public function __isset($column)
    {
	return (
		isset($this->values[$column]) || isset($this->relationships[$column]) || isset($this->hasOne[$column]) || isset($this->belongsTo[$column]) || isset($this->hasMany[$column])
		);
    }

    /**
     * Unsets object data.
     * @param  string $column Column name
     * @return void
     */
    public function __unset($column)
    {
	unset($this->values[$column], $this->_changed[$column], $this->relationships[$column]);
    }

    /**
     * Displays the primary key of a model when it is converted to a string.
     * @return string
     */
    public function __toString()
    {
	return (string) $this->pk();
    }

    /**
     * Clone the model into a new, non-existing instance.
     *
     * @return \Model
     */
    public function replicate()
    {
	$replicate = new static;
	$replicate->values = $this->values;
	$replicate->id = null;
	$replicate->set($this->pk, null);	
	return $replicate;
    }

    /**
     * Synonym replicate()
     * @return \Model
     */
    public function duplicate()
    {
	return $this->replicate();
    }

    /**
     * Handles setting of column
     * 
     * @param  string $column Column name
     * @param  mixed  $value  Column value
     * @return this model
     */
    public function set($column, $value)
    {
	$this->__set($column, $value);
	return $this;
    }

    /**
     * Allows serialization of only the object data and state, to prevent
     * "stale" objects being unserialized, which also requires less memory.
     * @return array
     */
    public function serialize()
    {
	foreach (array('pkValue', 'values', '_changed', '_loaded', '_saved', '_sorting') as $var) {
	    $data[$var] = $this->$var;
	}
	return serialize($data);
    }

    /**
     * Prepares the database connection and reloads the object.
     * @param string $data String for unserialization
     * @return  void
     */
    public function unserialize($data)
    {
	$this->_init();
	foreach (unserialize($data) as $name => $var) {
	    $this->{$name} = $var;
	}
	$this->reload();
    }

    /**
     * Shortcut method which will determine whether a row
     * with the current instances properties exists. If so, it will
     * preload those values (side effects).
     * Usage:
     * $model->{$this->pk} = 1;
     * if($model->exists()) {
     *  die('a lonesome death');
     * }
     * @return boolean
     */
    public function exists()
    {
	if ($this->pk() !== null) {
	    $this->copy($this, false);
	    return true;
	} else {
	    return false;
	}
    }

    /**
     * Copy values from a key/value array or another model/object
     * to this instance.
     * @param iterable $array
     * @return $this
     */
    public function copy($array, $excludePk = true)
    {
	foreach ($array as $key => $value) {
	    if ($excludePk && $key == $this->pk) {
		continue;
	    }
	    $this->$key = $value;
	}
	return $this;
    }

    /**
     * Tests if this object has a relationship to a different model,
     * or an array of different models.
     *   // Check if $model has the login role
     *   $model->has('roles', Model::forgery('role', array('name' => 'login')));
     *   // Check if $model has the login role
     *   {$model = new Model($id)}
     *   $model->has('roles', $model);
     *   // Check for the login role if you know the roles.id is 5
     *   $model->has('roles', 5);
     *   // Check for all of the following roles
     *   $model->has('roles', array(1, 2, 3, 4));
     * @param  string  $alias    Alias of the has_many "through" relationship
     * @param  mixed   $far_keys Related model, primary key, or an array of primary keys
     * @return Database_Result
     */
    public function has($alias, $far_keys)
    {
	$far_keys = ($far_keys instanceof Model) ? $far_keys->pk() : $far_keys;
	$far_keys = (array) $far_keys;
	self::$db->clear();
	self::$db->select('COUNT("*") as records_found')
		->from($this->hasMany[$alias]['through'])
		->where(array('AND', $this->hasMany[$alias]['foreign_key'] . '=' . $this->pk(), array('IN', $this->hasMany[$alias]['far_key'], $far_keys)))
		->buildQuery();
	$count = self::$db->fetchAssocRow();
	return (int) $count['records_found'] === count($far_keys);
    }

    /**
     * Adds a new relationship to between this model and another.
     *
     *     // Add the login role using a model instance
     *     $model->add('roles', Model::forgery('role', array('name' => 'login')));
     *     // Add the login role if you know the roles.id is 5
     *     $model->add('roles', 5);
     *     // Add multiple roles (for example, from checkboxes on a form)
     *     $model->add('roles', array(1, 2, 3, 4));
     *
     * @param  string  $alias    Alias of the has_many "through" relationship
     * @param  mixed   $far_keys Related model, primary key, or an array of primary keys
     * @return ORM
     */
    public function add($alias, $far_keys)
    {
	$far_keys = ($far_keys instanceof Model) ? $far_keys->pk() : $far_keys;
	$columns = array($this->hasMany[$alias]['foreign_key'], $this->hasMany[$alias]['far_key']);
	$foreign_key = $this->pk();
	$query = DB::insert($this->hasMany[$alias]['through'], $columns);
	foreach ((array) $far_keys as $key) {
	    $query->values(array($foreign_key, $key));
	}
	$query->execute($this->_db);
	return $this;
    }

    /**
     *
     * @param type $values
     */
    public function update_data($values)
    {
	$this->values += $values;
    }

    /**
     *
     * @return type
     */
    public function isEmpty()
    {
	return empty($this->values);
    }

    /**
     *
     * @param type $array
     * @return Model
     */
    public static function arrayToModel($array)
    {
	$result = array();
	foreach ($array as $key => $value) {
	    $result[$key] = new self($value);
	}
	return $result;
    }

    /**
     * Proxy method to Database list_columns.
     *
     * @return array
     */
    public function list_columns()
    {
	return self::$db->getTableFields($this->table);
    }

    /**
     * Prepares the model database connection, determines the table name,
     * and loads column information.
     *
     * @return void
     */
    private function _init()
    {
	$this->modelName = ucfirst(get_class($this));
	if (!is_object(self::$db)) {
	    self::$db = $this->getDbo()->clear();
	}
	if (empty($this->table)) {
	    $this->table = $this->modelName;
	}
	foreach ($this->belongsTo as $alias => $details) {
	    $defaults['model'] = $alias;
	    $defaults['foreign_key'] = Inflector::singular($alias) . $this->foreign_key_suffix;
	    $this->belongsTo[$alias] = array_merge($defaults, $details);
	}
	foreach ($this->hasOne as $alias => $details) {
	    $defaults['model'] = $alias;
	    $defaults['foreign_key'] = /* $this->modelName */ $this->getTable() . $this->foreign_key_suffix;
	    $this->hasOne[$alias] = array_merge($defaults, $details);
	}
	foreach ($this->hasMany as $alias => $details) {
	    $defaults['model'] = $alias;
	    $defaults['foreign_key'] = /* Inflector::singular($this->modelName) */$this->getTable() . $this->foreign_key_suffix;
	    $defaults['through'] = null;
	    $defaults['far_key'] = Inflector::singular($alias) . $this->foreign_key_suffix;
	    $this->hasMany[$alias] = array_merge($defaults, $details);
	}
	foreach ($this->manyMany as $alias => $det) {
	    $defaults['model'] = $alias;
	    $defaults['far_key'] = Inflector::singular($alias) . $this->foreign_key_suffix;
	    $this->manyMany[$alias] = array_merge($defaults, $det);
	}
	$this->reload_columns();
	$this->clear_model();
    }

    /**
     * Reload column definitions.
     *
     * @chainable
     * @param   boolean $force Force reloading
     * @return  ORM
     */
    public function reload_columns($force = false)
    {
	if ($force === true OR empty($this->tableColumns)) {
	    if (isset(Model::$_column_cache[$this->modelName])) {
		$this->tableColumns = Model::$_column_cache[$this->modelName];
	    } else {
		$this->tableColumns = $this->list_columns(true);
		Model::$_column_cache[$this->modelName] = $this->tableColumns;
	    }
	}
	return $this;
    }

    /**
     * Loads an array of values into into the current object.
     *
     * @chainable
     * @param  array $values Values to load
     * @return Model
     */
    private function _load_values(array $values)
    {
	if (array_key_exists($this->pk, $values)) {
	    if ($values[$this->pk] !== null) {
		$this->_loaded = $this->_saved = $this->_valid = true;
		$this->pkValue = $values[$this->pk];
	    } else {
		$this->_loaded = $this->_saved = $this->_valid = false;
	    }
	}
	// Related objects
	$related = array();
	foreach ($values as $column => $value) {
	    if (strpos($column, ':') === false) {
		$this->values[$column] = $value;
	    } else {
		list ($prefix, $column) = explode(':', $column, 2);
		$related[$prefix][$column] = $value;
	    }
	}

	if (!empty($related)) {
	    foreach ($related as $object => $values) {
		$this->_related_models($object)->_load_values($values);
	    }
	}
	return $this;
    }

    /**
     * Returns an ActiveRecord model for the given one-to-one related alias
     * @param  string $alias Alias name
     * @return ORM
     */
    private function _related_models($alias)
    {
	if (isset($this->relationships[$alias])) {
	    return $this->relationships[$alias];
	} elseif (isset($this->hasOne[$alias])) {
	    return $this->relationships[$alias] = Model::forgery($this->hasOne[$alias]['model']);
	} elseif (isset($this->belongsTo[$alias])) {
	    return $this->relationships[$alias] = Model::forgery($this->belongsTo[$alias]['model']);
	} else {
	    return false;
	}
    }

    /**
     * Get model columns
     * 
     * @return array
     */
    public function cols()
    {
	return $this->tableColumns;
    }

    /**
     * Get iterator
     * 
     * @return \ArrayIterator
     */
    public function getIterator()
    {
	return new ArrayIterator($this->values);
    }

}
