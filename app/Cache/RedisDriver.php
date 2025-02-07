<?php
/**
 * Trindade Framework
 *
 * Redis Cache Driver
 * 
 * Implements Redis-based caching for the Trindade Framework.
 * Provides high-performance caching using Redis server.
 * 
 * @package     Trindade
 * @author      Jorge Daniel Medina <https://github.com/jdanielcmedina>
 * @copyright   Copyright (c) 2025, Jorge Daniel Medina
 * @license     MIT License
 * @version     1.0.0
 */

namespace Trindade\Cache;

use Trindade\Interfaces\CacheInterface;
use Redis;

class RedisDriver implements CacheInterface {
    /**
     * Redis connection instance
     * 
     * @var Redis
     */
    private Redis $redis;
    
    /**
     * Cache key prefix
     * 
     * @var string
     */
    private string $prefix;
    
    /**
     * Constructor
     * 
     * Initializes the Redis cache driver with the specified configuration.
     * Establishes connection to Redis server and configures the connection.
     * 
     * @param array $config Configuration array containing Redis connection details
     * @throws \RuntimeException If Redis connection fails
     */
    public function __construct(array $config) {
        $this->prefix = $config['prefix'];
        $this->redis = new Redis();
        
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 6379;
        $timeout = $config['timeout'] ?? 0;
        $password = $config['password'] ?? null;
        
        if (!$this->redis->connect($host, $port, $timeout)) {
            throw new \RuntimeException("Falha ao conectar ao servidor Redis: $host:$port");
        }
        
        if ($password !== null && !$this->redis->auth($password)) {
            throw new \RuntimeException("Falha na autenticação com o servidor Redis");
        }
        
        if (isset($config['database'])) {
            $this->redis->select($config['database']);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function get(string $key, $default = null) {
        $value = $this->redis->get($this->prefix . $key);
        return $value !== false ? unserialize($value) : $default;
    }
    
    /**
     * {@inheritdoc}
     */
    public function set(string $key, $value, int $ttl = 3600): bool {
        $key = $this->prefix . $key;
        $value = serialize($value);
        
        if ($ttl > 0) {
            return $this->redis->setex($key, $ttl, $value);
        }
        
        return $this->redis->set($key, $value);
    }
    
    /**
     * {@inheritdoc}
     */
    public function remove(string $key): bool {
        return $this->redis->del($this->prefix . $key) > 0;
    }
    
    /**
     * {@inheritdoc}
     */
    public function clear(): bool {
        $keys = $this->redis->keys($this->prefix . '*');
        
        if (!empty($keys)) {
            return $this->redis->del($keys) > 0;
        }
        
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool {
        return $this->redis->exists($this->prefix . $key);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getMultiple(array $keys, $default = null): array {
        $prefixedKeys = array_map(function($key) {
            return $this->prefix . $key;
        }, $keys);
        
        $values = $this->redis->mGet($prefixedKeys);
        $result = [];
        
        foreach ($keys as $i => $key) {
            $value = $values[$i];
            $result[$key] = $value !== false ? unserialize($value) : $default;
        }
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function setMultiple(array $values, int $ttl = 3600): bool {
        if (empty($values)) {
            return true;
        }
        
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
        if (empty($keys)) {
            return true;
        }
        
        $prefixedKeys = array_map(function($key) {
            return $this->prefix . $key;
        }, $keys);
        
        return $this->redis->del($prefixedKeys) > 0;
    }
    
    /**
     * Gets the Redis connection instance
     * 
     * @return Redis The Redis connection instance
     */
    public function getRedis(): Redis {
        return $this->redis;
    }
} 