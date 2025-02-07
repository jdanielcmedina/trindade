<?php
/**
 * Trindade Framework
 *
 * File Cache Driver
 * 
 * Implements file-based caching for the Trindade Framework.
 * Stores cache data in files within a specified directory.
 * 
 * @package     Trindade
 * @author      Jorge Daniel Medina <https://github.com/jdanielcmedina>
 * @copyright   Copyright (c) 2025, Jorge Daniel Medina
 * @license     MIT License
 * @version     1.0.0
 */

namespace Trindade\Cache;

use Trindade\Interfaces\CacheInterface;

class FileDriver implements CacheInterface {
    /**
     * In-memory cache for frequently accessed items
     * 
     * @var array
     */
    private $memory = [];
    
    /**
     * Path to the cache directory
     * 
     * @var string
     */
    private $path;
    
    /**
     * Prefix for cache keys
     * 
     * @var string
     */
    private $prefix;
    
    /**
     * Constructor
     * 
     * Initializes the file cache driver with the specified configuration.
     * Creates the cache directory if it doesn't exist.
     * 
     * @param array $config Configuration array containing 'path' and 'prefix' keys
     * @throws \RuntimeException If cache directory cannot be created
     */
    public function __construct(array $config) {
        $this->path = rtrim($config['path'], '/');
        $this->prefix = $config['prefix'];
        
        if (!is_dir($this->path)) {
            mkdir($this->path, 0777, true);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function get(string $key, $default = null) {
        // Check memory cache first
        if (isset($this->memory[$key])) {
            return $this->memory[$key];
        }
        
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return $default;
        }
        
        $data = $this->readCache($file);
        if ($data === false || time() >= $data['expires']) {
            $this->remove($key);
            return $default;
        }
        
        $this->memory[$key] = $data['data'];
        return $data['data'];
    }
    
    /**
     * {@inheritdoc}
     */
    public function set(string $key, $value, int $ttl = 3600): bool {
        $file = $this->getFilePath($key);
        $this->memory[$key] = $value;
        
        $data = [
            'expires' => time() + $ttl,
            'data' => $value
        ];
        
        return $this->writeCache($file, $data);
    }
    
    /**
     * {@inheritdoc}
     */
    public function remove(string $key): bool {
        unset($this->memory[$key]);
        $file = $this->getFilePath($key);
        return !file_exists($file) || unlink($file);
    }
    
    /**
     * {@inheritdoc}
     */
    public function clear(): bool {
        $this->memory = [];
        $files = glob($this->path . '/*');
        
        $success = true;
        foreach ($files as $file) {
            if (is_file($file) && !unlink($file)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool {
        if (isset($this->memory[$key])) {
            return true;
        }
        
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return false;
        }
        
        $data = $this->readCache($file);
        return $data !== false && time() < $data['expires'];
    }
    
    /**
     * {@inheritdoc}
     */
    public function getMultiple(array $keys, $default = null): array {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function setMultiple(array $values, int $ttl = 3600): bool {
        $success = true;
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }
        return $success;
    }
    
    /**
     * {@inheritdoc}
     */
    public function removeMultiple(array $keys): bool {
        $success = true;
        foreach ($keys as $key) {
            if (!$this->remove($key)) {
                $success = false;
            }
        }
        return $success;
    }
    
    /**
     * Gets the full file path for a cache key
     * 
     * @param string $key The cache key
     * @return string The full file path
     */
    private function getFilePath(string $key): string {
        return $this->path . '/' . md5($key);
    }
    
    /**
     * Reads and unserializes cache data from a file
     * 
     * @param string $file Path to the cache file
     * @return array|false The cache data array or false on failure
     */
    private function readCache(string $file) {
        $data = file_get_contents($file);
        if ($data === false) {
            return false;
        }
        
        return unserialize($data);
    }
    
    /**
     * Serializes and writes cache data to a file
     * 
     * @param string $file Path to the cache file
     * @param array $data The cache data to write
     * @return bool True on success, false on failure
     */
    private function writeCache(string $file, array $data): bool {
        $dir = dirname($file);
        if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
            return false;
        }
        
        return file_put_contents($file, serialize($data)) !== false;
    }
} 