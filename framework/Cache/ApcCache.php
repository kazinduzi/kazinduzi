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

/**
 * Description of ApcCache.
 *
 * @author Emmanuel Ndayiragije <endayiragije@gmail.com>
 */
class ApcCache extends AbstractCache
{
    /**
     * {@inheritdoc}
     */
    protected function doDelete($key)
    {
        return apc_delete($key) || !apc_exists($key);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($key)
    {
        return apc_fetch($key);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        return apc_clear_cache() && apc_clear_cache('user');
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($key)
    {
        return apc_exists($key);
    }

    /**
     * {@inheritdoc}
     */
    protected function doPersist($key, $data, $ttl = 0)
    {
        return apc_store($key, $data, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetchMultiple(array $keys)
    {
        return apc_fetch($keys);
    }

    /**
     * {@inheritdoc}
     */
    protected function doPersistMultiple(array $keysAndValues, $ttl = 0)
    {
        $result = apc_store($keysAndValues, null, $ttl);

        return empty($result);
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        $info = apc_cache_info('', true);
        $sma = apc_sma_info();
        // @TODO - Temporary fix @see https://github.com/krakjoe/apcu/pull/42
        if (PHP_VERSION_ID >= 50500) {
            $info['num_hits'] = isset($info['num_hits']) ? $info['num_hits'] : $info['nhits'];
            $info['num_misses'] = isset($info['num_misses']) ? $info['num_misses'] : $info['nmisses'];
            $info['start_time'] = isset($info['start_time']) ? $info['start_time'] : $info['stime'];
        }

        return [
            CacheInterface::STATS_HITS             => $info['num_hits'],
            CacheInterface::STATS_MISSES           => $info['num_misses'],
            CacheInterface::STATS_UPTIME           => $info['start_time'],
            CacheInterface::STATS_MEMORY_USAGE     => $info['mem_size'],
            CacheInterface::STATS_MEMORY_AVAILABLE => $sma['avail_mem'],
        ];
    }
}
