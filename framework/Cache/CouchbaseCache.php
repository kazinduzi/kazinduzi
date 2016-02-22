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
 * Description of CouchbaseCache
 *
 * @author Emmanuel Ndayiragije <endayiragije@gmail.com>
 */

use \Couchbase;

class CouchbaseCache extends AbstractCache
{
    /**
     * @var Couchbase
     */
    private $couchbase;
    
    /**
     * Set couchbase
     * 
     * @param Couchbase $couchbse
     * @return Couchbase
     */
    public function setCouchbase(Couchbase $couchbse)
    {
        $this->couchbase = $couchbse;
        return $this;
    }
    
    /**
     * Get Couchbase
     * 
     * @return Couchbase
     */
    public function getCouchbase()
    {
        return $this->couchbase;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function doContains($key)
    {
        return null !== $this->couchbase->get($key);
    }

    /**
     * {@inheritdoc}     
     */
    protected function doDelete($key)
    {
        return $this->couchbase->delete($key);
    }

    /** 
     * {@inheritdoc}     
     */
    protected function doFetch($key)
    {
        return $this->couchbase->get($key) ?: false;
    }

    /** 
     * {@inheritdoc}     
     */
    protected function doFlush()
    {
        return $this->couchbase->flush();
    }
    
    /** 
     * {@inheritdoc}     
     */
    protected function doPersist($key, $data, $ttl = 0)
    {
        if ($ttl > 30 * 24 * 3600) {
            $ttl = time() + $ttl;
        }
        return $this->couchbase->set($key, $data, (int)$ttl);
    }
    
    /** 
     * {@inheritdoc}     
     */
    protected function doGetStats()
    {
        $stats   = $this->couchbase->getStats();
        $servers = $this->couchbase->getServers();
        $server = explode(':', $servers[0]);
        $key = $server[0] . ':' . '11210';
        $stats = $stats[$key];
        return array(
            CacheInterface::STATS_HITS   => $stats['get_hits'],
            CacheInterface::STATS_MISSES => $stats['get_misses'],
            CacheInterface::STATS_UPTIME => $stats['uptime'],
            CacheInterface::STATS_MEMORY_USAGE     => $stats['bytes'],
            CacheInterface::STATS_MEMORY_AVAILABLE => $stats['limit_maxbytes'],
        );
    }

}
