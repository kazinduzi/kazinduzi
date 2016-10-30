<?php
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
namespace Kazinduzi\Cache;

/*
 * Description of Memcached
 *
 * @author Emmanuel Ndayiragije <endayiragije@gmail.com>
 */

use Memcached;

class MemcachedCache extends AbstractCache
{
    /**
     * @var Memcached
     */
    private $memcached;

    /**
     * Sets the memcache instance to use.
     *
     * @param Memcached $memcached
     *
     * @return void
     */
    public function setMemcached(Memcached $memcached)
    {
        $this->memcached = $memcached;
    }

    /**
     * Gets the memcached instance used by the cache.
     *
     * @return Memcached|null
     */
    public function getMemcached()
    {
        return $this->memcached;
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($key)
    {
        return false !== $this->memcached->get($key)
            || $this->memcached->getResultCode() !== Memcached::RES_NOTFOUND;
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($key)
    {
        return $this->memcached->delete($key)
            || $this->memcached->getResultCode() === Memcached::RES_NOTFOUND;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($key)
    {
        return $this->memcached->get($key);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetchMultiple(array $keys)
    {
        return $this->memcached->getMulti($keys);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        return $this->memcached->flush();
    }

    /**
     * {@inheritdoc}
     */
    protected function doPersist($key, $data, $ttl = 0)
    {
        if ($ttl > 30 * 24 * 3600) {
            $ttl = time() + $ttl;
        }

        return $this->memcached->set($key, $data, (int) $ttl);
    }

    /**
     * {@inheritdoc}
     */
    protected function doPersistMultiple(array $keysAndValues, $ttl = 0)
    {
        if ($ttl > 30 * 24 * 3600) {
            $ttl = time() + (int) $ttl;
        }

        return $this->memcached->setMulti($keysAndValues, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        $stats = $this->memcached->getStats();
        $servers = $this->memcached->getServerList();
        $key = $servers[0]['host'].':'.$servers[0]['port'];
        $stats = $stats[$key];

        return [
            CacheInterface::STATS_HITS             => $stats['get_hits'],
            CacheInterface::STATS_MISSES           => $stats['get_misses'],
            CacheInterface::STATS_UPTIME           => $stats['uptime'],
            CacheInterface::STATS_MEMORY_USAGE     => $stats['bytes'],
            CacheInterface::STATS_MEMORY_AVAILABLE => $stats['limit_maxbytes'],
        ];
    }
}
