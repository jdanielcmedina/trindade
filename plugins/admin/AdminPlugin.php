<?php
/**
 * Trindade Framework
 *
 * Admin Plugin
 * 
 * Painel de administração completo.
 * Fornece interface para gestão do sistema.
 * 
 * @package     Trindade
 * @author      Jorge Daniel Medina <https://github.com/jdanielcmedina>
 * @copyright   Copyright (c) 2025, Jorge Daniel Medina
 * @license     MIT License
 * @version     1.0.0
 */

namespace Trindade\Plugins\Admin;

class AdminPlugin
{
    /**
     * Framework instance
     */
    protected $app;
    
    /**
     * Plugin configuration
     */
    protected array $config;
    
    /**
     * Constructor
     */
    public function __construct($app, array $config = [])
    {
        $this->app = $app;
        $this->config = array_merge([
            'prefix' => '/admin',
            'title' => 'Painel Admin',
            'menu' => [
                'dashboard' => ['icon' => 'home', 'title' => 'Dashboard'],
                'users' => ['icon' => 'users', 'title' => 'Utilizadores'],
                'settings' => ['icon' => 'settings', 'title' => 'Configurações']
            ],
            'auth' => [
                'table' => 'admin_users',
                'username_field' => 'email',
                'password_field' => 'password'
            ]
        ], $config);
    }
    
    /**
     * Initialize plugin
     */
    public function initialize(): void
    {
        // Registar plugin na app
        $this->app->admin = $this;
        
        // Criar tabelas necessárias
        $this->createTables();
        
        // Registar rotas
        $this->registerRoutes();
        
        // Registar middleware de autenticação
        $this->registerMiddleware();
    }
    
    /**
     * Create required tables
     */
    protected function createTables(): void
    {
        $this->app->db->create($this->config['auth']['table'], [
            'id' => ['INT', 'NOT NULL', 'AUTO_INCREMENT', 'PRIMARY KEY'],
            'name' => ['VARCHAR(100)', 'NOT NULL'],
            'email' => ['VARCHAR(100)', 'NOT NULL', 'UNIQUE'],
            'password' => ['VARCHAR(255)', 'NOT NULL'],
            'role' => ['VARCHAR(20)', 'NOT NULL', 'DEFAULT "admin"'],
            'active' => ['TINYINT(1)', 'NOT NULL', 'DEFAULT 1'],
            'last_login' => ['DATETIME', 'NULL'],
            'created_at' => ['DATETIME', 'NOT NULL', 'DEFAULT CURRENT_TIMESTAMP'],
            'updated_at' => ['DATETIME', 'NULL', 'ON UPDATE CURRENT_TIMESTAMP']
        ]);
    }
    
    /**
     * Register admin routes
     */
    protected function registerRoutes(): void
    {
        // Grupo de rotas admin
        $this->app->group($this->config['prefix'], function() {
            // Login
            $this->app->on('GET /login', [$this, 'showLogin']);
            $this->app->on('POST /login', [$this, 'login']);
            $this->app->on('GET /logout', [$this, 'logout']);
            
            // Dashboard
            $this->app->on('GET /', [$this, 'dashboard']);
            
            // Utilizadores
            $this->app->on('GET /users', [$this, 'listUsers']);
            $this->app->on('GET /users/create', [$this, 'createUserForm']);
            $this->app->on('POST /users', [$this, 'storeUser']);
            $this->app->on('GET /users/:id', [$this, 'editUserForm']);
            $this->app->on('POST /users/:id', [$this, 'updateUser']);
            $this->app->on('DELETE /users/:id', [$this, 'deleteUser']);
            
            // Configurações
            $this->app->on('GET /settings', [$this, 'showSettings']);
            $this->app->on('POST /settings', [$this, 'updateSettings']);
            
            // API
            $this->app->on('GET /api/stats', [$this, 'getStats']);
            
        }, [$this, 'authMiddleware']);
    }
    
    /**
     * Authentication middleware
     */
    protected function authMiddleware(): bool
    {
        $publicRoutes = ['/login'];
        $currentRoute = $_SERVER['REQUEST_URI'];
        
        if (in_array($currentRoute, $publicRoutes)) {
            return true;
        }
        
        if (!$this->app->session->get('admin_user')) {
            $this->app->redirect($this->config['prefix'] . '/login');
            return false;
        }
        
        return true;
    }
    
    /**
     * Show login form
     */
    public function showLogin(): void
    {
        $this->app->view('admin/login', [
            'title' => 'Login - ' . $this->config['title']
        ]);
    }
    
    /**
     * Process login
     */
    public function login(): void
    {
        $email = $this->app->post('email');
        $password = $this->app->post('password');
        
        $user = $this->app->db->get($this->config['auth']['table'], '*', [
            'email' => $email,
            'active' => 1
        ]);
        
        if ($user && password_verify($password, $user['password'])) {
            $this->app->session->set('admin_user', $user);
            $this->app->db->update($this->config['auth']['table'], 
                ['last_login' => date('Y-m-d H:i:s')],
                ['id' => $user['id']]
            );
            $this->app->redirect($this->config['prefix']);
        } else {
            $this->app->session->flash('error', 'Credenciais inválidas');
            $this->app->redirect($this->config['prefix'] . '/login');
        }
    }
    
    /**
     * Process logout
     */
    public function logout(): void
    {
        $this->app->session->remove('admin_user');
        $this->app->redirect($this->config['prefix'] . '/login');
    }
    
    /**
     * Show dashboard
     */
    public function dashboard(): void
    {
        $this->app->view('admin/dashboard', [
            'title' => 'Dashboard - ' . $this->config['title'],
            'menu' => $this->config['menu'],
            'stats' => $this->getStats()
        ]);
    }
    
    /**
     * Get system statistics
     */
    public function getStats(): array
    {
        return [
            'users' => $this->app->db->count($this->config['auth']['table']),
            'disk_usage' => $this->getDiskUsage(),
            'php_version' => PHP_VERSION,
            'server' => $_SERVER['SERVER_SOFTWARE']
        ];
    }
    
    /**
     * Get disk usage
     */
    protected function getDiskUsage(): array
    {
        $path = $this->app->config['paths']['storage'];
        $total = disk_total_space($path);
        $free = disk_free_space($path);
        $used = $total - $free;
        
        return [
            'total' => $this->formatBytes($total),
            'used' => $this->formatBytes($used),
            'free' => $this->formatBytes($free),
            'percent' => round(($used / $total) * 100)
        ];
    }
    
    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}