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
 * Interface for all cache drivers implementing this interface.
 *
 * @author Emmanuel Ndayiragije <endayiragije@gmail.com>
 */
interface CacheInterface
{
    const STATS_HITS = 'hits';
    const STATS_MISSES = 'misses';
    const STATS_UPTIME = 'uptime';
    const STATS_MEMORY_USAGE = 'memory_usage';
    const STATS_MEMORY_AVAILABLE = 'memory_available';

    /**
     * Checks if an cache-entry exists in the cache.
     *
     * @param string $key
     *
     * @return bool
     */
    public function contains($key);

    /**
     * Fetches an entry from the cache.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function fetch($key);

    /**
     * Puts data into the cache.
     *
     * @param string $key
     * @param mixed  $data
     * @param int    $ttl
     */
    public function persist($key, $data, $ttl = 0);

    /**
     * Deletes a cache entry.
     *
     * @param string $key A cache-key
     *
     * @return bool
     */
    public function delete($key);

    /**
     * Retrieves cached information from the data store.
     *
     * The server's statistics array has the following values:
     *
     * - hits: Number of keys that have been requested and found present.
     * - misses: Number of items that have been requested and not found.
     * - uptime: Time that the server is running.
     * - memory_usage: Memory used by this server to store items.
     * - memory_available: Memory allowed to use for storage.
     *
     * @return array|null
     */
    public function getStats();
}
