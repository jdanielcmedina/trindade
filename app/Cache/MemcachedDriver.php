<?php
/**
 * Trindade Framework
 *
 * Memcached Cache Driver
 * 
 * Implements Memcached-based caching for the Trindade Framework.
 * Provides distributed caching using Memcached servers.
 * 
 * @package     Trindade
 * @author      Jorge Daniel Medina <https://github.com/jdanielcmedina>
 * @copyright   Copyright (c) 2025, Jorge Daniel Medina
 * @license     MIT License
 * @version     1.0.0
 */

namespace Trindade\Cache;

use Trindade\Interfaces\CacheInterface;
use Memcached;

class MemcachedDriver implements CacheInterface {
    /**
     * Memcached connection instance
     * 
     * @var Memcached
     */
    private Memcached $memcached;
    
    /**
     * Cache key prefix
     * 
     * @var string
     */
    private string $prefix;
    
    /**
     * Constructor
     * 
     * Initializes the Memcached cache driver with the specified configuration.
     * Establishes connections to Memcached servers and configures the client.
     * 
     * @param array $config Configuration array containing Memcached connection details
     * @throws \RuntimeException If Memcached extension is not installed or connection fails
     */
    public function __construct(array $config) {
        if (!extension_loaded('memcached')) {
            throw new \RuntimeException('Memcached extension is not installed');
        }
        
        $this->prefix = $config['prefix'];
        $this->memcached = new Memcached();
        
        // Configurar servidores
        $servers = $config['servers'] ?? [
            ['host' => '127.0.0.1', 'port' => 11211, 'weight' => 100]
        ];
        
        foreach ($servers as $server) {
            $this->memcached->addServer(
                $server['host'],
                $server['port'],
                $server['weight'] ?? 100
            );
        }
        
        // Configurar opções
        if (isset($config['options']) && is_array($config['options'])) {
            $this->memcached->setOptions($config['options']);
        }
        
        // Verificar conexão
        $stats = $this->memcached->getStats();
        if (empty($stats)) {
            throw new \RuntimeException("Falha ao conectar aos servidores Memcached");
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function get(string $key, $default = null) {
        $value = $this->memcached->get($this->prefix . $key);
        
        if ($this->memcached->getResultCode() === Memcached::RES_NOTFOUND) {
            return $default;
        }
        
        return $value;
    }
    
    /**
     * {@inheritdoc}
     */
    public function set(string $key, $value, int $ttl = 3600): bool {
        return $this->memcached->set(
            $this->prefix . $key,
            $value,
            $ttl
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function remove(string $key): bool {
        return $this->memcached->delete($this->prefix . $key);
    }
    
    /**
     * {@inheritdoc}
     */
    public function clear(): bool {
        return $this->memcached->flush();
    }
    
    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool {
        $this->memcached->get($this->prefix . $key);
        return $this->memcached->getResultCode() !== Memcached::RES_NOTFOUND;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getMultiple(array $keys, $default = null): array {
        $prefixedKeys = array_map(function($key) {
            return $this->prefix . $key;
        }, $keys);
        
        $values = $this->memcached->getMulti($prefixedKeys);
        $result = [];
        
        foreach ($keys as $key) {
            $prefixedKey = $this->prefix . $key;
            $result[$key] = isset($values[$prefixedKey]) ? $values[$prefixedKey] : $default;
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
        
        $prefixedValues = [];
        foreach ($values as $key => $value) {
            $prefixedValues[$this->prefix . $key] = $value;
        }
        
        return $this->memcached->setMulti($prefixedValues, $ttl);
    }
    
    /**
     * {@inheritdoc}
     */
    public function removeMultiple(array $keys): bool {
        if (empty($keys)) {
            return true;
        }
        
        $success = true;
        foreach ($keys as $key) {
            if (!$this->remove($key)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Gets the Memcached connection instance
     * 
     * @return Memcached The Memcached connection instance
     */
    public function getMemcached(): Memcached {
        return $this->memcached;
    }
} 