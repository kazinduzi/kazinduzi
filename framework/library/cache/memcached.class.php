<?php

defined('KAZINDUZI_PATH') || exit('No direct script access allowed');
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
class CacheMemcached extends Cache
{
    /**
     *  Memcache has a maximum cache lifetime of 30 days.
     */
    const CACHE_CEILING = 2592000;

    /**
     * Memcache resource.
     *
     * @var Memcache
     */
    protected $memcached;

    /**
     * Flags to use when storing values.
     *
     * @var string
     */
    protected $_flag;

    /**
     * The configuration for memcache config data.
     *
     * @var array
     */
    protected $_config = [];


    /**
     * Constructor of the Memcache Class.
     *
     * @param array $config
     *
     * @throws Exception
     */
    protected function __construct(array $config)
    {
        $this->_config = $config;
        // Check for the memcache extention
        if (!extension_loaded('memcached')) {
            throw new Exception('Memcached PHP extention not loaded');
        }
        // Setup Memcache
        $this->memcached = new \Memcached();
        foreach ($this->_config['servers'] as $server) {
            if (!$server) {
                throw new Exception('No Memcache servers defined in configuration');
            }
            $this->memcached->addServer($server['host'], $server['port']);
        }
    }

    /**
     * Test if a cache is available for the given id and (if yes) return it (false else).
     *
     * @param string $id Cache id
     *
     * @return string|false cached datas
     */
    public function load($id)
    {
        $tmp = $this->memcached->get($this->_sanitize_id($id));
        if (is_array($tmp) && isset($tmp[0])) {
            return $tmp[0];
        }

        return false;
    }

    /**
     * Test if a cache is available or not (for the given id).
     *
     * @param string $id Cache id
     *
     * @return mixed|false (a cache is not available) or "last modified" timestamp (int) of the available cache record
     */
    public function test($id)
    {
        $tmp = $this->memcached->get($id);
        if (is_array($tmp)) {
            return $tmp[1];
        }

        return false;
    }

    /**
     * @param type $id
     * @param type $default
     *
     * @return type
     */
    public function get($id)
    {
        return $this->load($id);
    }

    /**
     * @param type $id
     * @param type $data
     * @param type $ttl
     *
     * @return type
     */
    public function set($id, $data, $ttl = self::TTL, $overwrite = false)
    {
        if ($ttl > self::CACHE_CEILING) { // If the lifetime is greater than the ceiling
            $ttl = self::CACHE_CEILING + time(); // Set the lifetime to maximum cache time
        } elseif ($ttl > 0) {
            $ttl += time();
        } else {
            $ttl = 0; // Normalise the lifetime
        }
        try {
            $bool = $this->memcached->set($this->_sanitize_id($id), [$data, time(), $ttl], $ttl);
        } catch (\Exception $e) {
            print_r($e);
        }

        return $bool;
    }

    /**
     * Stores variable var with key only if such key doesn't exist at the server yet.
     *
     * @param string $id
     * @param mixed  $data
     * @param int    $ttl
     *
     * @return bool
     */
    public function add($id, $data, $ttl = 60)
    {
        return $this->_memcached->add($this->_sanitize_id($id), [$data, time(), $ttl], $ttl);
    }

    /**
     * @param string $id
     * @param int    $timeout
     *
     * @return bool
     */
    public function delete($id, $timeout = 0)
    {
        return $this->memcached->delete($this->_sanitize_id($id), $timeout);
    }

    /**
     * Remove a cache record.
     *
     * @param string $id Cache id
     *
     * @return bool True if no problem
     */
    public function remove($id, $timeout = 0)
    {
        return $this->memcached->delete($id, $timeout);
    }

    /**
     * @return type
     */
    public function deleteAll()
    {
        $result = $this->memcached->flush();
        // We must sleep after flushing, or overwriting will not work!
        // @see http://php.net/manual/en/function.memcache-flush.php#81420
        $time = time() + 1; //one second future
        while (time() < $time);

        return $result;
    }

    /**
     * Clean some cache records.
     *
     * @return bool
     */
    public function clean()
    {
        $result = $this->memcached->flush();
        $time = time() + 1; //one second future
        while (time() < $time);

        return $result;
    }

    /**
     * Cache Info.
     *
     * @return mixed array on success, false on failure
     */
    public function info()
    {
        return $this->_memcached->getStats();
    }

    /**
     * Get Cache Metadata.
     *
     * @param 	mixed	key to get cache metadata on
     *
     * @return mixed false on failure, array on success.
     */
    public function metadata($id)
    {
        $stored = $this->_memcached->get($id);
        if (count($stored) !== 3) {
            return false;
        }
        list($data, $time, $ttl) = $stored;

        return [
                    'expire'       => $time + $ttl,
                    'mtime'        => $time,
                    'data'         => $data,
        ];
    }

    /**
     * @param type $id
     * @param type $step
     *
     * @return type
     */
    public function increment($id, $step = 1)
    {
        return $this->memcached->increment($id, $step);
    }

    /**
     * @param type $id
     * @param type $step
     *
     * @return type
     */
    public function decrement($id, $step = 1)
    {
        return $this->memcached->decrement($id, $step);
    }
}
