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
class CacheFile extends Cache
{
    /**
     * @var type
     */
    protected $_config = [];

    /**
     * @var string the caching directory
     */
    protected $_cache_dir;

    /**
     * @param string $string
     *
     * @return string
     */
    protected static function filename($string)
    {
        return sha1($string).'.cache';
    }

    /**
     * Constructor.
     *
     * @param array $options
     */
    protected function __construct(array $options)
    {
        $this->_config = $options;
        try {
            $directory = $this->_config['cache_dir'];
            $this->_cache_dir = new SplFileInfo($directory);
            if (!$this->_cache_dir->isDir()) {
                $this->_cache_dir = $this->_createDir($directory, 0777, true);
            } else {
                $this->_cache_dir->isWritable() ? null : chmod($this->_cache_dir->getRealPath(), 0777);
            }
        } catch (Exception $e) {
            print_r($e);
        }

        // If the defined directory is a file, get outta here
        if ($this->_cache_dir->isFile()) {
            throw new Exception('Unable to create cache directory as a file already exists : '.$this->_cache_dir->getRealPath());
        }
        // Check the read status of the directory
        if (!$this->_cache_dir->isReadable()) {
            throw new Exception('Unable to read from the cache directory '.$this->_cache_dir->getRealPath());
        }

        // Check the write status of the directory
        if (!$this->_cache_dir->isWritable()) {
            throw new Exception('Unable to write to the cache directory '.$this->_cache_dir->getRealPath());
        }
    }

    /**
     * Retrieve a cached value entry by id.
     *
     * @param   string   id of cache to entry
     * @param   string   default value to return if cache miss
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function get($id, $default = null)
    {
        $filename = self::filename($this->_sanitize_id($id));
        $directory = $this->_resolveDir($filename);
        // Wrap operations in try/catch to handle notices
        try {
            // Open file
            $file = new SplFileInfo($directory.DS.$filename);
            // If file does not exist
            if (!$file->isFile()) {
                return $default;
            } else {
                // Open the file and parse data
                $created = $file->getMTime();
                $data = $file->openFile();
                $lifetime = $data->fgets();
                // If we're at the EOF at this point, corrupted!
                if ($data->eof()) {
                    throw new Exception(__METHOD__.' corrupted cache file!');
                }
                $cache = '';
                while ($data->eof() === false) {
                    $cache .= $data->fgets();
                }
                // Test the expiry
                if (($created + (int) $lifetime) < time()) {
                    $this->_deleteFile($file, null, true);

                    return $default;
                } else {
                    return unserialize($cache);
                }
            }
        } catch (ErrorException $e) {
            if ($e->getCode() === E_NOTICE) {
                throw new Exception(__METHOD__.' failed to unserialize cached object with message : '.$e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Set a value to cache with id and lifetime.
     *
     * @param   string   id of cache entry
     * @param   string   data to set to cache
     * @param   int  lifetime in seconds
     *
     * @return bool
     */
    public function set($id, $data, $lifetime = self::TTL, $overwrite = false)
    {
        $filename = self::filename($this->_sanitize_id($id));
        $directory = $this->_resolveDir($filename);
        // If lifetime is null
        if ($lifetime === null) {
            $lifetime = isset($this->_config['ttl']) ? $this->_config['ttl'] : self::TTL;
        }
        $dir = new SplFileInfo($directory);
        if (!$dir->isDir()) {
            if (!mkdir($directory, 0777, true)) {
                throw new Exception(__METHOD__.' unable to create directory : '.$directory);
            }
            // chmod to solve potential umask issues
            chmod($directory, 0777);
        }
        // Open file to inspect
        $resouce = new SplFileInfo($directory.DS.$filename);
        $file = $resouce->openFile('w');
        try {
            $data = $lifetime."\n".serialize($data);
            $file->fwrite($data, strlen($data));

            return (bool) $file->fflush();
        } catch (ErrorException $e) {
            // If serialize through an error exception
            if ($e->getCode() === E_NOTICE) {
                throw new Exception(__METHOD__.' failed to serialize data for caching with message : '.$e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Delete a cache entry based on id.
     *
     * @param string id to remove from cache
     *
     * @return bool
     */
    public function delete($id)
    {
        $filename = self::filename($this->_sanitize_id($id));
        $directory = $this->_resolveDir($filename);

        return $this->_deleteFile(new SplFileInfo($directory.DS.$filename), null, true);
    }

    /**
     * Alias for delete cache entry.
     *
     * @param string $id
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
        return $this->_deleteFile($this->_cache_dir, true);
    }

    /**
     * Alias for deleteAll.
     *
     * @return bool
     */
    public function clean()
    {
        return $this->deleteAll();
    }

    /**
     * Garbage collection method that cleans any expired
     * cache entries from the cache.
     *
     * @return void
     */
    public function gc()
    {
        $this->_deleteFile($this->_cache_dir, true, false, true);
    }

    /**
     * Deletes files recursively and returns false on any errors.
     *
     * @param   SplFileInfo  file
     * @param   bool  retain the parent directory
     * @param   bool  hide_errors to prevent all exceptions interrupting exec
     * @param   bool  only expired files
     *
     * @throws Exception
     *
     * @return bool
     */
    private function _deleteFile(SplFileInfo $file, $retain_parent_directory = false, $hide_errors = false, $only_expired = false)
    {
        try {
            if ($file->isFile()) {
                try {
                    if (isset($this->_config['ignore_on_delete']) && in_array($file->getFilename(), $this->_config['ignore_on_delete'])) {
                        $delete = false;
                    } elseif ($only_expired === false) {
                        $delete = true;
                    } else {
                        $json = $file->openFile('r')->current();
                        $data = json_decode($json);
                        $delete = $data->expiry < time();
                    }
                    if ($delete === true) {
                        return @unlink($file->getRealPath());
                    } else {
                        return false;
                    }
                } catch (ErrorException $e) {
                    if ($e->getCode() === E_WARNING) {
                        throw new Exception(__METHOD__.' failed to delete file : '.$file->getRealPath());
                    }
                }
            } elseif ($file->isDir()) {
                $files = new DirectoryIterator($file->getPathname());
                while ($files->valid()) {
                    $name = $files->getFilename();
                    if ($name != '.' and $name != '..') {
                        $fp = new SplFileInfo($files->getRealPath());
                        $this->_deleteFile($fp);
                    }
                    $files->next();
                }
                if ($retain_parent_directory) {
                    return true;
                }
                try {
                    // (fixes Windows PHP which has permission issues with open iterators)
                    unset($files);
                    // Try to remove the parent directory
                    return rmdir($file->getRealPath());
                } catch (ErrorException $e) {
                    if ($e->getCode() === E_WARNING) {
                        throw new Exception(__METHOD__.' failed to delete directory : '.$file->getRealPath());
                    }
                    throw $e;
                }
            } else {
                return false;
            }
        } catch (Exception $e) {
            if ($hide_errors === true) {
                return false;
            }
            throw $e;
        }
    }

    /**
     * Resolves the cache directory real path from the filename.
     *
     * @param   string   filename to resolve
     *
     * @return string
     */
    private function _resolveDir($filename)
    {
        return $this->_cache_dir->getRealPath().DS.$filename[0].$filename[1];
    }

    /**
     * Makes the cache directory if it doesn't exist. Simply a wrapper for
     * `mkdir` to ensure DRY principles.
     *
     * @see     http://php.net/manual/en/function.mkdir.php
     *
     * @param   string   directory
     * @param   string   mode
     * @param   string   recursive
     * @param   string   context
     *
     * @throws Exception
     *
     * @return SplFileInfo
     */
    private function _createDir($directory, $mode = 0777, $recursive = false, $context = null)
    {
        if ($context === null) {
            if (!mkdir($directory, $mode, $recursive)) {
                throw new Exception('Failed to create the defined cache directory : '.$directory);
            }
        } else {
            if (!mkdir($directory, $mode, $recursive, $context)) {
                throw new Exception('Failed to create the defined cache directory : '.$directory);
            }
        }
        chmod($directory, $mode);

        return new SplFileInfo($directory);
    }
}
