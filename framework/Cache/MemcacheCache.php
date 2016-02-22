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
 * Description of Memcache
 *
 * @author Emmanuel Ndayiragije <endayiragije@gmail.com>
 */

use \Memcache;

class MemcacheCache extends AbstractCache
{
    /**
     * @var Memcache
     */
    private $memcache;
        
    /**
     * Set memcache instance
     * @param Memcache $memcache
     */
    public function setMemcache(Memcache $memcache)
    {
        $this->memcache = $memcache;
    }
    
    /**
     * Get Memcache instance
     * 
     * @return Memcache
     */
    public function getMemcache()
    {
        return $this->memcache;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function doContains($key)
    {
        $flags = null;
        $this->memcache->get($key, $flags);
        
        //if memcache has changed the value of "flags", it means the value exists
        return ($flags !== null);
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($key)
    {
        // Memcache::delete() returns false if entry does not exist
        return $this->memcache->delete($key) || ! $this->doContains($key);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($key)
    {
        return $this->memcache->get($key);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        return $this->memcache->flush();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function doPersist($key, $data, $ttl = 0)
    {
        if ($ttl > 30 * 24 * 3600) {
            $ttl = time() + $ttl;
        }
        return $this->memcache->set($key, $data, 0, (int)$ttl);
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        $stats = $this->memcache->getStats();
        return array(
            CacheInterface::STATS_HITS => $stats['get_hits'],
            CacheInterface::STATS_MISSES => $stats['get_misses'],
            CacheInterface::STATS_UPTIME => $stats['uptime'],
            CacheInterface::STATS_MEMORY_USAGE => $stats['bytes'],
            CacheInterface::STATS_MEMORY_AVAILABLE => $stats['limit_maxbytes'],
        );
    }    

}
