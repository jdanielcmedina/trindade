<?php
/**
 * Trindade Framework
 *
 * Cache System
 * 
 * Main cache handler for the Trindade Framework.
 * Provides a unified interface for different cache drivers (File, Redis, Memcached).
 * Handles driver initialization, configuration, and cache operations.
 * 
 * @package     Trindade
 * @author      Jorge Daniel Medina <https://github.com/jdanielcmedina>
 * @copyright   Copyright (c) 2025, Jorge Daniel Medina
 * @license     MIT License
 * @version     1.0.0
 */

namespace Trindade;

use Trindade\Interfaces\CacheInterface;
use Trindade\Cache\FileDriver;
use Trindade\Cache\RedisDriver;
use Trindade\Cache\MemcachedDriver;

class Cache implements CacheInterface {
    /**
     * The active cache driver instance
     */
    private CacheInterface $driver;
    
    /**
     * Cache configuration settings
     */
    private array $config;
    
    /**
     * Constructor
     * 
     * @param array $config Cache configuration array
     * @throws \RuntimeException If driver initialization fails
     */
    public function __construct(array $config) {
        $this->config = array_merge([
            'driver' => 'file',
            'prefix' => 'trindade:',
            'path' => __DIR__ . '/../storage/cache'
        ], $config);
        
        $this->initializeDriver();
    }
    
    /**
     * Initialize cache driver
     */
    private function initializeDriver(): void {
        switch ($this->config['driver']) {
            case 'file':
                $this->driver = new FileDriver($this->config);
                break;
            case 'redis':
                $this->driver = new RedisDriver($this->config);
                break;
            case 'memcached':
                $this->driver = new MemcachedDriver($this->config);
                break;
            default:
                throw new \RuntimeException("Driver de cache não suportado: {$this->config['driver']}");
        }
    }
    
    /**
     * Get a value from cache
     */
    public function get(string $key, $default = null) {
        return $this->driver->get($this->prefixKey($key), $default);
    }
    
    /**
     * Set a value in cache
     */
    public function set(string $key, $value, int $ttl = 3600): bool {
        return $this->driver->set($this->prefixKey($key), $value, $ttl);
    }
    
    /**
     * Remove a value from cache
     */
    public function remove(string $key): bool {
        return $this->driver->remove($this->prefixKey($key));
    }
    
    /**
     * Clear all cache
     */
    public function clear(): bool {
        return $this->driver->clear();
    }
    
    /**
     * Check if key exists in cache
     */
    public function has(string $key): bool {
        return $this->driver->has($this->prefixKey($key));
    }
    
    /**
     * Get multiple values from cache
     */
    public function getMultiple(array $keys, $default = null): array {
        $prefixedKeys = array_map([$this, 'prefixKey'], $keys);
        return $this->driver->getMultiple($prefixedKeys, $default);
    }
    
    /**
     * Set multiple values in cache
     */
    public function setMultiple(array $values, int $ttl = 3600): bool {
        $prefixedValues = [];
        foreach ($values as $key => $value) {
            $prefixedValues[$this->prefixKey($key)] = $value;
        }
        return $this->driver->setMultiple($prefixedValues, $ttl);
    }
    
    /**
     * Remove multiple values from cache
     */
    public function removeMultiple(array $keys): bool {
        $prefixedKeys = array_map([$this, 'prefixKey'], $keys);
        return $this->driver->removeMultiple($prefixedKeys);
    }
    
    /**
     * Add prefix to cache key
     */
    private function prefixKey(string $key): string {
        return $this->config['prefix'] . $key;
    }
    
    /**
     * Get current cache driver
     */
    public function getDriver(): CacheInterface {
        return $this->driver;
    }
    
    /**
     * Get current cache configuration
     */
    public function getConfig(): array {
        return $this->config;
    }
}