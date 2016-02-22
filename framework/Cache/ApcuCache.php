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
 * APCu cache
 *
 * @author Emmanuel Ndayiragije <endayiragije@gmail.com>
 */
class ApcuCache extends AbstractCache
{
    /**
     * {@inheritdoc}
     */
    protected function doDelete($key)
    {
        return apcu_delete($key) || ! apcu_exists($key);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($key)
    {
        return apcu_fetch($key);
    }   

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        return apcu_clear_cache() && apcu_clear_cache('user');
    }    

    /**
     * {@inheritdoc}
     */
    protected function doContains($key)
    {
        return apcu_exists($key);
    }

    /**
     * {@inheritdoc}
     */
    protected function doPersist($key, $data, $ttl = 0)
    {
        return apcu_store($key, $data, $ttl);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function doFetchMultiple(array $keys) 
    {
        return apcu_fetch($keys);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function doPersistMultiple(array $keysAndValues, $ttl = 0)
    {
        $result = apcu_store($keysAndValues, null, $ttl);
        return empty($result);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        $info = apcu_cache_info('', true);
        $sma  = apcu_sma_info();        
        return array(
            CacheInterface::STATS_HITS => $info['num_hits'],
            CacheInterface::STATS_MISSES => $info['num_misses'],
            CacheInterface::STATS_UPTIME => $info['start_time'],
            CacheInterface::STATS_MEMORY_USAGE => $info['mem_size'],
            CacheInterface::STATS_MEMORY_AVAILABLE => $sma['avail_mem'],
        );
    }

}
