<?php

defined('KAZINDUZI_PATH') or exit('No direct script access allowed');
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
 * Description of Apc cache class.
 *
 * @author Emmanuel_Leonie
 */
class CacheApc extends Cache
{
    /**
     * Config.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Flag to check if APCu is enabled.
     *
     * @var bool
     */
    protected $apcu = false;

    /**
     * Check for existence of the APC extension This method cannot be invoked externally. The driver must
     * be instantiated using the `Cache::instance()` method.
     *
     * @param  array configuration
     *
     * @throws Exception
     */
    protected function __construct(array $config)
    {
        if (!extension_loaded('apc') && !extension_loaded('apcu')) {
            throw new Exception('PHP APC(u) extension is not available.');
        }
        $this->apcu = extension_loaded('apcu');
        $this->config = $config;
    }

    /**
     * Checks if a key of an array of keys exists already in the cache.
     *
     * @param string|array $key
     *
     * @return true if the key exists, otherwise false,
     *              Or if an array was passed to keys, then an array is returned that contains all existing keys,
     *              Or an empty array if none exist.
     */
    protected function keyExists($key)
    {
        return $this->apcu ? apcu_exists($key) : apc_exists($key);
    }

    /**
     * @staticvar type $config
     *
     * @param string $id
     * @param mixed  $data
     * @param int    $ttl
     *
     * @return bool
     */
    public function set($id, $data, $ttl = null, $overwrite = false)
    {
        if ($this->keyExists($this->_sanitize_id($id)) && !$overwrite) {
            throw new ErrorException('Cache entry with this key {'.$id.'} exists');
        }

        if (null === $ttl && !isset($this->config['default_expire'])) {
            $ttl = parent::TTL;
        } else {
            $ttl = $this->config['default_expire'];
        }

        return $this->apcu ? apcu_store($this->_sanitize_id($id), $data, $ttl) : apc_store($this->_sanitize_id($id), $data, $ttl);
    }

    /**
     * @param string $id
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($id)
    {
        return $this->apcu ? apcu_fetch($this->_sanitize_id($id)) : apc_fetch($this->_sanitize_id($id));
    }

    /**
     * Delete a cache entry based on id.
     *
     * @param   string   id to remove from cache
     *
     * @return bool
     */
    public function delete($id)
    {
        return $this->apcu ? apcu_delete($this->_sanitize_id($id)) : apc_delete($this->_sanitize_id($id));
    }

    /**
     * Alias for delete.
     *
     * @param   string   id to remove from cache
     *
     * @return bool
     */
    public function remove($id)
    {
        return $this->delete($id);
    }

    /**
     * Delete all cache entries.
     * Beware of using this method when
     * using shared memory cache systems, as it will wipe every
     * entry within the system for all clients.
     *
     * @return bool
     */
    public function deleteAll()
    {
        return $this->apcu ? apcu_clear_cache('user') : apc_clear_cache('user');
    }

    /**
     * Remove all cache entries.
     * Beware of using this method when
     * using shared memory cache systems, as it will wipe every
     * entry within the system for all clients.
     *
     * @return bool
     */
    public function removeAll()
    {
        return $this->deleteAll();
    }

    /**
     * Alias to deleteAll.
     *
     * @return bool
     */
    public function clean()
    {
        return $this->deleteAll();
    }

    /**
     * Return an array of stored cache ids.
     *
     * @return array of stored cache ids (string)
     */
    public function getIds()
    {
        $rv = [];
        $array = $this->apcu ? apcu_cache_info('user', false) : apc_cache_info('user', false);
        foreach ($array['cache_list'] as $row) {
            $rv[] = $row['info'];
        }

        return $rv;
    }

    /**
     * Increments a given value by the step value supplied.
     * Useful for shared counters and other persistent integer based
     * tracking.
     *
     * @param   string    id of cache entry to increment
     * @param   int       step value to increment by
     *
     * @return int
     * @return bool
     */
    public function increment($id, $step = 1)
    {
        return $this->apcu ? apcu_inc($id, $step) : apc_inc($id, $step);
    }

    /**
     * Decrements a given value by the step value supplied.
     * Useful for shared counters and other persistent integer based
     * tracking.
     *
     * @param   string    id of cache entry to decrement
     * @param   int       step value to decrement by
     *
     * @return int
     * @return bool
     */
    public function decrement($id, $step = 1)
    {
        return $this->apcu ? apcu_dec($id, $step) : apc_dec($id, $step);
    }
}
