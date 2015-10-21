<?php
/**
 * Kazinduzi Framework (http://framework.kazinduzi.com/)
 *
 * @author    Emmanuel Ndayiragije <endayiragije@gmail.com>
 * @link      http://kazinduzi.com
 * @copyright Copyright (c) 2010-2013 Kazinduzi. (http://www.kazinduzi.com)
 * @license   http://kazinduzi.com/page/license MIT License
 * @package   Kazinduzi
 */
class CacheRedis extends Cache {
    protected $_config;
    protected $_Redis;

    /**
     *
     * @return type
     */
    public static function enabled() {
		return extension_loaded('redis');
	}
    /**
     *
     * @param array $_config
     * @throws Exception
     */
    protected function __construct(array $config) {
		if (!class_exists('Redis')) {
			throw new Exception('Redis engine is not available.');
		}
        $this->_config = array_merge(array(
                    'driver'    => 'Redis',
                    'prefix'    => null,
                    'server'    => '127.0.0.1',
                    'port'      => 6379,
                    'password'  => false,
                    'timeout'   => 0,
                    'persistent'=> true
                ), $config);
        if (!$this->connect()) {
            throw new Exception('Connection to the Redis server fails');
        }
	}

    /**
     * Connects to a Redis server
     * @return boolean true if Redis server was connected
     */
    protected function connect() {
        $return = false;
		try {
			$this->_Redis = new Redis();
			if (empty($this->_config['persistent'])) {
				$return = $this->_Redis->connect($this->_config['server'], $this->_config['port'], $this->_config['timeout']);
			} else {
				$return = $this->_Redis->pconnect($this->_config['server'], $this->_config['port'], $this->_config['timeout']);
			}
		} catch (RedisException $e) {
			print_r($e);
		}
		if ($return && $this->_config['password']) {
			$return = $this->_Redis->auth($this->_config['password']);
		}
		return $return;
    }

    protected function keyExists($keys) {
        return $this->_Redis->get($keys);
    }

    /**
     *
     * @param type $key
     * @param type $value
     * @param type $ttl
     * @return type
     */
    public function set($key, $value, $ttl = self::TTL, $overwrite = false) {
        $key = $this->_sanitize_id($key);
        var_dump($overwrite);
        if ($this->keyExists($key) && $overwrite==false) {
            throw new ErrorException('Cache entry with this key {'.$key.'} exists');
        }
		if (!is_int($value)) {
			$value = serialize($value);
		}
		if ($ttl === 0) {
			return $this->_Redis->set($key, $value);
		}
		return $this->_Redis->setex($key, $ttl, $value);
	}

    /**
     * Read a key from the cache
     *
     * @param string $key Identifier for the data
     * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
     */
	public function get($key) {
        $key = $this->_sanitize_id($key);
		$value = $this->_Redis->get($key);
        if (ctype_digit($value)) {
			$value = (int)$value;
		}
		if ($value !== false && is_string($value)) {
			$value = unserialize($value);
		}
		return $value;
	}

    /**
     * Increments the value of an integer cached key
     *
     * @param string $key Identifier for the data
     * @param integer $offset How much to increment
     * @return New incremented value, false otherwise
     * @throws CacheException when you try to increment with compress = true
     */
	public function increment($key, $offset = 1) {
		return (int)$this->_Redis->incrBy($key, $offset);
	}

    /**
     * Decrements the value of an integer cached key
     *
     * @param string $key Identifier for the data
     * @param integer $offset How much to subtract
     * @return New decremented value, false otherwise
     * @throws CacheException when you try to decrement with compress = true
     */
	public function decrement($key, $offset = 1) {
		return (int)$this->_Redis->decrBy($key, $offset);
	}

    /**
     *
     * @param type $key
     * @return type
     */
    public function delete($key) {
        $key = $this->_sanitize_id($key);
        return $this->_Redis->delete($key) > 0;
    }

    /**
     *
     * @return boolean
     */
    public function deleteAll() {
		$keys = $this->_Redis->getKeys($this->_config['prefix'] . '*');
		$this->_Redis->del($keys);
		return true;
    }

    /**
     *
     * @return type
     */
    public function removeAll() {
        return $this->deleteAll();
    }

    /**
     *
     * @return string
     */
    public function groups() {
		$result = array();
		foreach ($this->_config['groups'] as $group) {
			$value = $this->_Redis->get($this->_config['prefix'] . $group);
			if (!$value) {
				$value = 1;
				$this->_Redis->set($this->_config['prefix'] . $group, $value);
			}
			$result[] = $group . $value;
		}
		return $result;
	}

    /**
     * Increments the group value to simulate deletion of all keys under a group
     * old values will remain in storage until they expire.
     *
     * @return boolean success
     */
	public function clearGroup($group) {
		return (bool)$this->_Redis->incr($this->_config['prefix'] . $group);
	}

    /**
     * Disconnects from the redis server
     *
     * @return void
     */
	public function __destruct() {
		if (!$this->_config['persistent']) {
			$this->_Redis->close();
		}
	}

}