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
 * Description of FileCache
 *
 * @author Emmanuel Ndayiragije <endayiragije@gmail.com>
 */

use SplFileInfo, DirectoryIterator, Exception, InvalidArgumentException;

class FileCache extends AbstractCache
{
    const EXTENSION = '.cache.data';
    
    protected $directory;
    private $extension;
    private $umask;
    private $directoryStringLength;
    private $extensionStringLength;
    private $isRunningOnWindows;
    
    /**
     * Constructor
     * 
     * @param strin $directory
     * @param string $extension
     * @param int $umask
     * @throws InvalidArgumentException
     */
    public function __construct($directory, $extension = self::EXTENSION, $umask = 0002)
    {        
        $this->directory = new SplFileInfo($directory);
        if (false === $this->directory->isDir()) {
            $this->directory = $this->createDirectory($directory, 0777, true);
        } else {
            $this->directory->isWritable() ?: chmod($this->directory->getRealPath(), 0777);
        }
        
        $this->umask = $umask;
        $this->extension = (string)$extension;
        $this->directoryStringLength = strlen($this->directory->getRealPath());
        $this->extensionStringLength = strlen($this->extension);
        $this->isRunningOnWindows = defined('PHP_WINDOWS_VERSION_BUILD');        
        
        if ($this->directory->isFile()) {
	    throw new Exception(sprintf(
                    'Unable to create directory "%s", as the file already exists',
                    $this->directory->getRealPath()
                ));
	}
	
        if (!$this->directory->isReadable()) {
	    throw new Exception(sprintf(
                    'The directory "%s" is not readable.',
                    $this->directory->getRealPath()
                ));
	}
	
        if (!$this->directory->isWritable()) {
	    throw new Exception(sprintf(
                    'The directory "%s" is not writable.', 
                    $this->directory->getRealPath()
                ));
	}
        
    }
    
    /**
     * Get directory
     * 
     * @return SplFileInfo
     */
    public function getDirectory()
    {
        return $this->directory;
    }
    
    /**
     * Get extension
     * 
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
    }
    
    /**
     * Get filename
     * 
     * @param string $key
     * @return string
     */
    protected function getFilename($key)
    {
        $hash = hash('sha256', $key);

        // This ensures that the filename is unique and that there are no invalid chars in it.
        if ('' === $key
            || ((strlen($key) * 2 + $this->extensionStringLength) > 255)
            || ($this->isRunningOnWindows && ($this->directoryStringLength + 4 + strlen($key) * 2 + $this->extensionStringLength) > 258)
        ) {
            // Most filesystems have a limit of 255 chars for each path component. On Windows the the whole path is limited
            // to 260 chars (including terminating null char). Using long UNC ("\\?\" prefix) does not work with the PHP API.
            // And there is a bug in PHP (https://bugs.php.net/bug.php?id=70943) with path lengths of 259.
            // So if the id in hex representation would surpass the limit, we use the hash instead. The prefix prevents
            // collisions between the hash and bin2hex.
            $filename = '_' . $hash;
        } else {
            $filename = bin2hex($key);
        }

        return substr($hash, 0, 2)
            . DIRECTORY_SEPARATOR
            . $filename
            . $this->extension;
    }    
    
    /**
     * Writes a string content to file in an atomic way.
     *
     * @param string $filename Path to the file where to write the data.
     * @param string $content  The content to write     *
     * @return bool
     */
    protected function writeFile($filename, $content)
    {
        // Check if directory of filename exists,
        $filedir = pathinfo($filename, PATHINFO_DIRNAME);
        $basename = pathinfo($filename, PATHINFO_BASENAME);
        $directory = $this->getDirectory()->getPathname() . DIRECTORY_SEPARATOR . $filedir;
        
        if ( ! is_dir($directory)) {
            mkdir($directory, 0777 & (~$this->umask), true);
        }
                    
	// Open file to inspect        
	$resouce = new SplFileInfo($directory  . DIRECTORY_SEPARATOR . $basename);
	$file = $resouce->openFile('w');
	if ($file->fwrite($content, strlen($content))) {
	    return $file->fflush();
	}
        
        return false;   

    }
    
    /**
     * @return \Iterator
     */
    private function getIterator()
    {
        return new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->directory->getRealPath(), \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
    }

    /**
     * @param string $name The filename
     *
     * @return bool
     */
    private function isFilenameEndingWithExtension($name)
    {
        return '' === $this->extension || strrpos($name, $this->extension) === (strlen($name) - $this->extensionStringLength);
    }
            
    /**
     * Makes the cache directory if it doesn't exist. Simply a wrapper for
     * `mkdir` to ensure DRY principles
     * @see     http://php.net/manual/en/function.mkdir.php
     * @param   string   directory     
     * @param   string   context
     * @return  SplFileInfo
     * @throws  InvalidArgumentException
     */
    private function createDirectory($path, $context = null) 
    {
        if (! mkdir($path, 0777 & (~$this->umask), true, $context) && !is_dir($path)) {
            throw new InvalidArgumentException(sprintf(
                    'Failed to create the defined cache directory: %s', 
                    $path
                ));
        }	        
	return new SplFileInfo($path);
    }
    
    /**
     * Delete file from cache
     * 
     * @param SplFileInfo $file
     * @return boolean
     */
    private function deleteFile(SplFileInfo $file) 
    {
        
        if ($file->isFile()) {
            
            return unlink($file->getRealPath());            

        } elseif ($file->isDir()) {
            
            $files = new DirectoryIterator($file->getPathname());
            while ($files->valid()) {
                $name = $files->getFilename();
                if ($name != '.' && $name != '..')	{
                    $finfo = new SplFileInfo($files->getRealPath());
                    $this->deleteFile($finfo);
                }
                $files->next();
            }
            
            // (fixes Windows PHP which has permission issues with open iterators)
            unset($files);
            // Try to remove the parent directory
            return rmdir($file->getRealPath());

        } else {
            return false;
        }
	
    }
    
    // Implements the abstract cache methods from AbstractCache,
    // =========================================================
    // The file based caching (filesystem) needs some tweaks to work like other cache drivers.
        
    /**
     * {@inheritdoc}
     */
    protected function doContains($key)
    {
        $filename = $this->getFilename($key);        
        $file = new SplFileInfo($this->getDirectory() . DIRECTORY_SEPARATOR . $filename);
        if (false === $file->isFile()) {
            return false;
        }
        
        $data = $file->openFile();
        $ttl = $data->fgets();
        return $ttl === 0 || $ttl > time();
        
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($key)
    {
        $filename = $this->getFilename($key);
        $file = new SplFileInfo($this->getDirectory() . DIRECTORY_SEPARATOR . $filename);
        return $this->deleteFile($file);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($key)
    {
        $filename = $this->getFilename($key);        
        $file = new SplFileInfo($this->getDirectory() . DIRECTORY_SEPARATOR . $filename);
        
        if (false === $file->isFile()) {
            return false;
        }
        
        $created  = $file->getMTime();
        $data = $file->openFile();
        $ttl = $data->fgets();
        
        $cache = '';
        while ($data->eof() === false) {
            $cache .= $data->fgets();
        }
        
        // Test the expiry
        if (($created + (int)$ttl) < time()) {
            $this->_deleteFile($file, null, true);
            return false;
        }
        
        return unserialize($cache);        

    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        foreach ($this->getIterator() as $name => $file) {
            if ($file->isDir()) {
                // Remove the intermediate directories which have been created to balance the tree. It only takes effect
                // if the directory is empty. If several caches share the same directory but with different file extensions,
                // the other ones are not removed.
                @rmdir($name);
            } elseif ($this->isFilenameEndingWithExtension($name)) {
                // If an extension is set, only remove files which end with the given extension.
                // If no extension is set, we have no other choice than removing everything.
                @unlink($name);
            }
        }

        return true;
    }  

    /**
     * {@inheritdoc}
     */
    protected function doPersist($key, $data, $ttl = 0)
    {
        if ($ttl > 0) {
            $ttl = time() + $ttl;
        }
        $data = serialize($data);
        $filename = $this->getFilename($key);
        return $this->writeFile($filename, $ttl . PHP_EOL . $data);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        $usage = 0;
        foreach ($this->getIterator() as $name => $file) {
            if (! $file->isDir() && $this->isFilenameEndingWithExtension($name)) {
                $usage += $file->getSize();
            }
        }

        $free = disk_free_space($this->directory);

        return array(
            CacheInterface::STATS_HITS => null,
            CacheInterface::STATS_MISSES => null,
            CacheInterface::STATS_UPTIME => null,
            CacheInterface::STATS_MEMORY_USAGE => $usage,
            CacheInterface::STATS_MEMORY_AVAILABLE => $free,
        );
    }

}
