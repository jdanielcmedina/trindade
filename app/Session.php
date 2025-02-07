<?php
/**
 * Trindade Framework
 *
 * Session Management System
 * 
 * Handles PHP session operations.
 * Provides secure session management and storage.
 * 
 * @package     Trindade
 * @author      Jorge Daniel Medina <https://github.com/jdanielcmedina>
 * @copyright   Copyright (c) 2025, Jorge Daniel Medina
 * @license     MIT License
 * @version     1.0.0
 */

namespace Trindade;

class Session {
    /**
     * Session configuration
     * 
     * @var array
     */
    protected $config;
    
    /**
     * Whether session has been started
     * 
     * @var bool
     */
    protected $started = false;
    
    /**
     * Constructor
     * 
     * Initializes session with configuration and security settings.
     * 
     * @param array $config Session configuration array
     */
    public function __construct(array $config = []) {
        $this->config = array_merge([
            'name' => 'trindade_session',
            'lifetime' => 7200,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httpOnly' => true
        ], $config);
        
        $this->configure();
    }
    
    /**
     * Configures PHP session settings
     * 
     * @return void
     */
    protected function configure(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        session_name($this->config['name']);
        
        session_set_cookie_params(
            $this->config['lifetime'],
            $this->config['path'],
            $this->config['domain'],
            $this->config['secure'],
            $this->config['httpOnly']
        );
        
        // Prevent session fixation
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.use_trans_sid', 0);
        ini_set('session.cookie_httponly', 1);
        
        if ($this->config['secure']) {
            ini_set('session.cookie_secure', 1);
        }
    }
    
    /**
     * Starts the session
     * 
     * @return bool Success status
     */
    public function start(): bool {
        if ($this->started) {
            return true;
        }
        
        if (session_start()) {
            $this->started = true;
            
            // Regenerate session ID periodically for security
            if (!isset($_SESSION['_last_regenerate']) || 
                time() - $_SESSION['_last_regenerate'] > 300) {
                $this->regenerate();
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Gets a session value
     * 
     * @param string $key Session key
     * @param mixed $default Default value
     * @return mixed Session value
     */
    public function get(string $key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Sets a session value
     * 
     * @param string $key Session key
     * @param mixed $value Session value
     * @return void
     */
    public function set(string $key, $value): void {
        $_SESSION[$key] = $value;
    }
    
    /**
     * Removes a session value
     * 
     * @param string $key Session key
     * @return void
     */
    public function remove(string $key): void {
        unset($_SESSION[$key]);
    }
    
    /**
     * Checks if a session value exists
     * 
     * @param string $key Session key
     * @return bool Whether value exists
     */
    public function has(string $key): bool {
        return isset($_SESSION[$key]);
    }
    
    /**
     * Gets all session data
     * 
     * @return array Session data
     */
    public function all(): array {
        return $_SESSION;
    }
    
    /**
     * Clears all session data
     * 
     * @return void
     */
    public function clear(): void {
        $_SESSION = [];
    }
    
    /**
     * Destroys the session
     * 
     * @return bool Success status
     */
    public function destroy(): bool {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->clear();
            $this->started = false;
            
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
            
            return session_destroy();
        }
        
        return true;
    }
    
    /**
     * Regenerates the session ID
     * 
     * @param bool $deleteOldSession Whether to delete old session
     * @return bool Success status
     */
    public function regenerate(bool $deleteOldSession = true): bool {
        if (session_regenerate_id($deleteOldSession)) {
            $_SESSION['_last_regenerate'] = time();
            return true;
        }
        return false;
    }
    
    /**
     * Gets the current session ID
     * 
     * @return string Session ID
     */
    public function getId(): string {
        return session_id();
    }
    
    /**
     * Sets flash data (available only for next request)
     * 
     * @param string $key Flash key
     * @param mixed $value Flash value
     * @return void
     */
    public function flash(string $key, $value): void {
        $_SESSION['_flash'][$key] = $value;
    }
    
    /**
     * Gets flash data
     * 
     * @param string $key Flash key
     * @param mixed $default Default value
     * @return mixed Flash value
     */
    public function getFlash(string $key, $default = null) {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
    
    /**
     * Checks if flash data exists
     * 
     * @param string $key Flash key
     * @return bool Whether flash exists
     */
    public function hasFlash(string $key): bool {
        return isset($_SESSION['_flash'][$key]);
    }
    
    /**
     * Gets all flash data
     * 
     * @return array Flash data
     */
    public function getAllFlash(): array {
        $flash = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flash;
    }
    
    /**
     * Generates CSRF token
     * 
     * @return string CSRF token
     */
    public function generateCsrfToken(): string {
        $token = bin2hex(random_bytes(32));
        $this->set('_csrf_token', $token);
        return $token;
    }
    
    /**
     * Validates CSRF token
     * 
     * @param string $token Token to validate
     * @return bool Whether token is valid
     */
    public function validateCsrfToken(string $token): bool {
        return hash_equals($this->get('_csrf_token', ''), $token);
    }
}
