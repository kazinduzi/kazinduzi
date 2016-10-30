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
class CacheXcache extends Cache
{
    protected $_config;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->_config =
            array_merge(
                [
                    'engine'        => 'Xcache',
                    'prefix'        => String::slugify(APP_PATH).'_',
                    'ttl'           => self::TTL,
                    'PHP_AUTH_USER' => 'admin',
                    'PHP_AUTH_PW'   => ('chispa'),
                ], $config);
    }

    protected function keyExists($keys)
    {
        return xcache_isset($keys);
    }

    /**
     * @param type $key
     * @param type $value
     * @param type $ttl
     *
     * @return type
     */
    public function set($key, $value, $ttl = self::TTL, $overwrite = false)
    {
        $key = $this->_sanitize_id($key);
        if ($this->keyExists($key)) {
            throw new ErrorException('Cache entry with this key {'.$key.'} exists');
        }
        $expires = time() + $ttl;
        xcache_set($key.'_expires', $expires, $ttl);

        return xcache_set($key, $value, $ttl);
    }

    /**
     * @param type $id
     *
     * @return type
     */
    public function get($key)
    {
        $key = $this->_sanitize_id($key);
        if (xcache_isset($key)) {
            $time = time();
            $cachetime = (int) (xcache_get($key.'_expires'));
            if ($cachetime < $time || ($time + $this->_config['ttl']) < $cachetime) {
                return false;
            }

            return xcache_get($key);
        }

        return false;
    }

    /**
     * @param type $key
     * @param type $offset
     *
     * @return type
     */
    public function increment($key, $offset = 1)
    {
        $key = $this->_sanitize_id($key);

        return xcache_inc($key, $offset);
    }

    /**
     * @param type $key
     * @param type $offset
     *
     * @return type
     */
    public function decrement($key, $offset = 1)
    {
        $key = $this->_sanitize_id($key);

        return xcache_dec($key, $offset);
    }

    public function delete($key)
    {
        $key = $this->_sanitize_id($key);

        return xcache_unset($key);
    }

    /**
     * @return bool
     */
    public function deleteAll()
    {
        $this->_auth();
        $max = xcache_count(XC_TYPE_VAR);
        for ($i = 0; $i < $max; $i++) {
            xcache_clear_cache(XC_TYPE_VAR, $i);
        }
        $this->_auth(true);

        return true;
    }

    /**
     * @return type
     */
    public function clear()
    {
        return $this->deleteAll();
    }
}
