<?php
/**
 * Trindade Framework
 *
 * Cache Interface
 * 
 * Defines the contract for cache drivers in the Trindade Framework.
 * All cache drivers must implement these methods.
 * 
 * @package     Trindade
 * @author      Jorge Daniel Medina <https://github.com/jdanielcmedina>
 * @copyright   Copyright (c) 2025, Jorge Daniel Medina
 * @license     MIT License
 * @version     1.0.0
 */

namespace Trindade\Interfaces;

interface CacheInterface {
    /**
     * Get a value from cache
     * 
     * @param string $key The cache key
     * @param mixed $default Default value if key not found
     * @return mixed The cached value or default
     */
    public function get(string $key, $default = null);
    
    /**
     * Set a value in cache
     * 
     * @param string $key The cache key
     * @param mixed $value The value to cache
     * @param int $ttl Time to live in seconds
     * @return bool True on success, false on failure
     */
    public function set(string $key, $value, int $ttl = 3600): bool;
    
    /**
     * Remove a value from cache
     * 
     * @param string $key The cache key
     * @return bool True on success, false on failure
     */
    public function remove(string $key): bool;
    
    /**
     * Clear all cache
     * 
     * @return bool True on success, false on failure
     */
    public function clear(): bool;
    
    /**
     * Check if key exists in cache
     * 
     * @param string $key The cache key
     * @return bool True if exists, false otherwise
     */
    public function has(string $key): bool;
    
    /**
     * Get multiple values from cache
     * 
     * @param array $keys Array of cache keys
     * @param mixed $default Default value for missing keys
     * @return array Array of key => value pairs
     */
    public function getMultiple(array $keys, $default = null): array;
    
    /**
     * Set multiple values in cache
     * 
     * @param array $values Array of key => value pairs
     * @param int $ttl Time to live in seconds
     * @return bool True on success, false on failure
     */
    public function setMultiple(array $values, int $ttl = 3600): bool;
    
    /**
     * Remove multiple values from cache
     * 
     * @param array $keys Array of cache keys
     * @return bool True on success, false on failure
     */
    public function removeMultiple(array $keys): bool;
} 