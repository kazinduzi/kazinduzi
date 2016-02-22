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

namespace Kazinduzi\Cache;

/**
 * Abstract class to be extended by all CacheStorage class.
 * 
 * @author Emmanuel Ndayiragije <endayiragije@gmail.com>
 */
abstract class AbstractCache implements CacheInterface
{
    const KAZINDUZI_NS_CACHE_KEY = 'Kazinduzi-Ns-Cache-Key#%s';
    
    /**
     * The namespace to prefix all cache ids with.
     *
     * @var string
     */
    private $ns = '';
    
    /**
     * Set the cache namespace
     * 
     * @param string $ns    
     */
    public function setNamespace($ns) 
    {
        $this->ns = $ns;        
    }
    
    /**
     * Get the namespace
     * 
     * @return string
     */
    public function getNamespace()
    {
        return $this->ns;
    }
    
    /**
     * {@inheritdoc}
     */
    public function contains($key) 
    {
        return $this->doContains($this->getNamespacedKey($key));
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        return $this->doDelete($this->getNamespacedKey($key));
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($key)
    {
        $this->getNamespacedKey($key);
        return $this->doFetch($this->getNamespacedKey($key));
    }
    
    /**
     * {@inheritdoc}
     */
    public function fetchMultiple(array $keys)
    {
        if (empty($keys)) {
            return [];
        }
        
        $namespacedKeys = array_combine($keys, array_map(array($this, 'getNamespacedKey'), $keys));
        $items = $this->doFetchMultiple($namespacedKeys);
        $foundItems = [];
        foreach ($namespacedKeys as $requestedKey => $namespacedKey) {
            if (isset($items[$namespacedKey]) || array_key_exists($namespacedKey, $items)) {
                $foundItems[$requestedKey] = $items[$namespacedKey];
            }
        }
        return $foundItems;
    }
    
    /**
     * {@inheritdoc}
     */
    public function persist($key, $data, $ttl = 0) 
    {
        return $this->doPersist($this->getNamespacedKey($key), $data, $ttl);
    }
    
    /**
     * {@inheritdoc}
     */
    public function persistMultiple(array $keysAndValues, $ttl = 0)
    {
        $namespacedKeysAndValues = [];
        foreach ($keysAndValues as $key => $value) {
            $namespacedKeysAndValues[$this->getNamespacedKey($key)] = $value;
        }
        return $this->doPersistMultiple($namespacedKeysAndValues, $ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function getStats() 
    {
        return $this->doGetStats();
    }
    
    /**
     * {@inheritDoc}
     */
    public function flushAll()
    {
        return $this->doFlush();
    }
    
    /**
     * {@inheritDoc}
     */
    public function deleteAll()
    {
        $namespaceCacheKey = $this->getNamespaceCacheKey();
        if ($this->doPersist($namespaceCacheKey, null)) {
            return true;
        }
        return false;
    }
    
    /**
     * Default implementation of doFetchMultiple. Each driver that supports multi-get should owerwrite it.
     *
     * @param array $keys Array of keys to retrieve from cache
     * @return array Array of values retrieved for the given keys.
     */
    protected function doFetchMultiple(array $keys)
    {
        $returnValues = array();
        foreach ($keys as $key) {
            if (false !== ($item = $this->doFetch($key)) || $this->doIsHit($key)) {
                $returnValues[$key] = $item;
            }
        }
        return $returnValues;
    }
        
    /**
     * Default implementation of doPersistMultiple. 
     * Each driver that supports multi-put should override it.
     * 
     * @param array $keysAndValues
     * @param int $ttl The lifetime. If != 0, sets a specific TTL for these cache entries (0 => infinite TTL)
     * @return boolean
     */
    protected function doPersistMultiple(array $keysAndValues, $ttl = 0)
    {
        $success = true;
        foreach ($keysAndValues as $key => $value) {
            if (!$this->doPersist($key, $value, $ttl)) {
                $success = false;
            }
        }
        return $success;
    }
    
    /**
     * Prefixes the passed key with the configured namespace value.
     *
     * @param string $key
     * @return string
     */
    private function getNamespacedKey($key)
    {
        return sprintf('%s[%s]', $this->ns, str_replace(array('/', '\\', ' '), '_', $key));
    }
    /**
     * Returns the namespace cache key.
     *
     * @return string
     */
    private function getNamespaceCacheKey()
    {
        return sprintf(self::KAZINDUZI_NS_CACHE_KEY, $this->ns);
    }
    
    /**
     * Fetches an entry from the cache.
     *
     * @param string $id The id of the cache entry to fetch.
     * @return mixed|false The cached data or FALSE, if no cache entry exists for the given id.
     */
    abstract protected function doFetch($key);
    
    /**
     * Puts data into the cache.
     *
     * @param string $key
     * @param string $data
     * @param int $ttl If != 0, sets a specific lifetime for this cache entry (0 => infinite lifeTime).
     * @return bool TRUE if the entry was successfully stored in the cache, FALSE otherwise.
     */
    abstract protected function doPersist($key, $data, $ttl = 0);
    
    /**
     * Tests if an entry exists in the cache.
     *
     * @param string $key The cache id of the entry to check for.
     * @return bool
     */
    abstract protected function doContains($key);
    
    /**
     * Deletes a cache entry.
     *
     * @param string $key
     * @return bool TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    abstract protected function doDelete($key);
    
    /**
     * Flushes all cache entries.
     * 
     * @return bool TRUE if the cache entries were successfully flushed, FALSE otherwise.
     */
    abstract protected function doFlush();
    
    /**
     * Retrieves cached information from the data store.
     * 
     * @return array|null An associative array with server's statistics if available, NULL otherwise.
     */
    abstract protected function doGetStats();
    
}