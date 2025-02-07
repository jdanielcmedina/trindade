<?php
/**
 * Trindade Framework
 *
 * Cookie Management System
 * 
 * Handles browser cookie operations.
 * Provides secure cookie management and storage.
 * 
 * @package     Trindade
 * @author      Jorge Daniel Medina <https://github.com/jdanielcmedina>
 * @copyright   Copyright (c) 2025, Jorge Daniel Medina
 * @license     MIT License
 * @version     1.0.0
 */

namespace Trindade;

/**
 * Cookie Class - Browser Cookie Management
 * 
 * @package Trindade
 */
class Cookie {
    /**
     * Sets a cookie value
     *
     * @param string $key Cookie name
     * @param mixed $value Cookie value
     * @param int $expires Expiration time in seconds
     * @return bool Success status
     */
    public function set(string $key, $value, int $expires = 3600): bool {
        return setcookie($key, $value, time() + $expires, '/');
    }
    
    /**
     * Gets a cookie value
     *
     * @param string $key Cookie name
     * @param mixed $default Default value if cookie doesn't exist
     * @return mixed Cookie value or default
     */
    public function get(string $key, $default = null) {
        return $_COOKIE[$key] ?? $default;
    }
    
    /**
     * Removes a cookie
     *
     * @param string $key Cookie name
     * @return bool Success status
     */
    public function remove(string $key): bool {
        return setcookie($key, '', time() - 3600, '/');
    }
}