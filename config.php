<?php
/**
 * Trindade Framework
 *
 * Configuration File
 * 
 * This file contains all the configuration settings for the framework.
 * Each section is documented with its purpose and available options.
 * 
 * @package     Trindade
 * @author      Jorge Daniel Medina <https://github.com/jdanielcmedina>
 * @copyright   Copyright (c) 2025, Jorge Daniel Medina
 * @license     MIT License
 * @version     1.0.0
 */

return [
    /**
     * Database Configuration
     * 
     * Settings for database connection and behavior.
     * Supports multiple database types through PDO.
     * 
     * @var array
     */
    'database' => [
        'type' => 'mysql',          // Database type (mysql, pgsql, sqlite)
        'host' => 'localhost',      // Database host
        'database' => 'database',   // Database name
        'username' => 'root',       // Database username
        'password' => '',           // Database password
        'charset' => 'utf8mb4',     // Character set
        'collation' => 'utf8mb4_unicode_ci', // Collation
        'prefix' => '',             // Table prefix
    ],
    
    /**
     * Mail Configuration
     * 
     * Settings for email sending through SMTP.
     * Supports various SMTP servers and security options.
     * 
     * @var array
     */
    'mail' => [
        'username' => '',           // SMTP username
        'password' => '',           // SMTP password
        'fromName' => 'Trindade',   // Default sender name
        'smtp' => [
            'host' => '',           // SMTP host
            'port' => 587,          // SMTP port
            'secure' => 'tls',      // tls or ssl
            'auth' => true          // Enable SMTP authentication
        ]
    ],
    
    /**
     * Path Configuration
     * 
     * Defines the paths for various framework components.
     * All paths are relative to the project root.
     * 
     * @var array
     */
    'paths' => [
        'base' => __DIR__,          // Project root directory
        'public' => __DIR__ . '/public',    // Public web directory
        'storage' => __DIR__ . '/storage',  // Storage directory
        'cache' => __DIR__ . '/storage/cache', // Cache storage
        'logs' => __DIR__ . '/storage/logs',   // Log files
        'uploads' => __DIR__ . '/storage/uploads', // File uploads
        'views' => __DIR__ . '/views',      // View templates
    ],
    
    /**
     * Session Configuration
     * 
     * Settings for PHP session handling.
     * Controls session behavior and security.
     * 
     * @var array
     */
    'session' => [
        'name' => 'trindade_session', // Session name
        'lifetime' => 7200,         // Session lifetime in seconds
        'path' => '/',              // Cookie path
        'domain' => '',             // Cookie domain
        'secure' => false,          // Require HTTPS
        'httpOnly' => true          // HTTP only cookie
    ],
    
    /**
     * Cache Configuration
     * 
     * Settings for the caching system.
     * Supports multiple cache drivers with their specific configurations.
     * 
     * Available drivers:
     * - file: File-based caching (default)
     * - redis: Redis server caching
     * - memcached: Memcached server caching
     * 
     * @var array
     */
    'cache' => [
        'driver' => 'file',         // Cache driver (file, redis, memcached)
        'prefix' => 'trindade:',    // Cache key prefix
        'path' => __DIR__ . '/storage/cache', // File cache path
        
        // Redis Configuration
        'redis' => [
            'host' => '127.0.0.1',  // Redis server host
            'port' => 6379,         // Redis server port
            'password' => null,      // Redis server password
            'database' => 0         // Redis database index
        ],
        
        // Memcached Configuration
        'memcached' => [
            'servers' => [
                [
                    'host' => '127.0.0.1', // Memcached server host
                    'port' => 11211,       // Memcached server port
                    'weight' => 100        // Server weight for load balancing
                ]
            ]
        ]
    ],
    
    /**
     * Debug Mode
     * 
     * When true, enables detailed error messages and logging.
     * Should be set to false in production.
     * 
     * @var bool
     */
    'debug' => true,
    
    /**
     * Timezone Setting
     * 
     * Default timezone for date/time functions.
     * Should match your application's primary timezone.
     * 
     * @var string
     */
    'timezone' => 'UTC',
    
    /**
     * Locale Setting
     * 
     * Default locale for internationalization.
     * Used by the translation system.
     * 
     * @var string
     */
    'locale' => 'en',
    
    /**
     * Application Key
     * 
     * Used for encryption and security features.
     * Should be a random, secure string.
     * 
     * @var string
     */
    'key' => '' // Generate a secure key and place it here
]; 