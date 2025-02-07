<?php
/**
 * Trindade Framework
 *
 * Core System
 * 
 * Main framework core component.
 * Handles routing, components and application flow.
 * 
 * @package     Trindade
 * @author      Jorge Daniel Medina <https://github.com/jdanielcmedina>
 * @copyright   Copyright (c) 2025, Jorge Daniel Medina
 * @license     MIT License
 * @version     1.0.0
 */

namespace Trindade;


/**
 * Trindade Class - Framework Core
 * 
 * @package Trindade
 * @property array $config Global configurations
 * @property array $routes Registered routes
 * @property string $currentPrefix Current group prefix
 * @property array $plugins Loaded plugins
 * @property bool $debug Debug mode
 * @property Session $session Session manager
 * @property Cookie $cookie Cookie manager
 * @property Database $db Database manager
 * @property Cache $cache Cache system
 * @property Mail $mail Email system
 * @property File $file File manager
 * @property Hash $hash Hashing utilities
 * @property Utils $utils Utility functions
 * @property Logger $log Logging system
 * @property Assets $assets Asset manager
 * @property Lang $lang Language system
 */
class Core {
    protected static ?Core $instance = null;
    /**
     * Framework configuration
     * 
     * @var array
     */
    protected array $config;
    /**
     * Registered routes
     * 
     * @var array
     */
    protected array $routes = [];
    /**
     * Current route prefix for groups
     * 
     * @var string
     */
    protected string $currentPrefix = '';
    protected array $plugins = [];
    protected bool $debug = false;
    
    // Componentes
    public Session $session;
    public Cookie $cookie;
    public Database $db;
    public Cache $cache;
    public Mail $mail;
    public File $file;
    public Hash $hash;
    public Utils $utils;
    public Logger $log;
    public Assets $assets;
    public Lang $lang;

    protected bool $found = false;
    protected string $parent = '';
    protected array $shortcuts = [];

    protected array $notFoundHandlers = [];

    /**
     * Constructor
     * 
     * Initializes the framework core with configuration and sets up error handling.
     * 
     * @param array $config Framework configuration array
     */
    public function __construct(array $config = []) {
        // Load configuration
        $this->config = $this->loadConfig();
        $this->config = array_merge($this->config, $config);
        
        // Set debug mode
        $this->debug = $this->config['debug'] ?? false;
        
        // Setup error handling
        $this->setupErrorHandling();
        
        // Initialize basic components
        $this->session = new Session($this->config['session'] ?? []);
        $this->cookie = new Cookie();
        $this->hash = new Hash();
        $this->utils = new Utils();
        
        // Initialize database with error handling
        try {
            $this->db = new Database($this->config['database'] ?? []);
        } catch (\PDOException $e) {
            // Se a configuração estiver vazia ou incompleta
            if (empty($this->config['database']) || 
                empty($this->config['database']['host']) || 
                empty($this->config['database']['database'])) {
                $this->showSetupRequired();
                exit;
            }
            
            // Se houver erro de conexão, mostra uma mensagem amigável
            $this->showDatabaseError($e->getMessage());
            exit;
        }
        
        // Initialize remaining components
        $this->cache = new Cache($this->config['cache'] ?? []);
        $this->mail = new Mail($this->config['mail'] ?? []);
        $this->file = new File($this->config['paths']['uploads'] ?? __DIR__ . '/../storage/uploads');
        $this->log = new Logger($this->config['paths']['logs'] ?? __DIR__ . '/../storage/logs');
        
        // Set timezone
        date_default_timezone_set($this->config['timezone'] ?? 'UTC');

        // Assets
        $publicPath = $this->config['paths']['public'] ?? __DIR__ . '/public';
        $this->assets = new Assets($publicPath);
        
        // Lang
        $langPath = $this->config['paths']['lang'] ?? __DIR__ . '/lang';
        $this->lang = new Lang($langPath);

        static::$instance = $this;
    }

    /**
     * Loads and merges configuration
     * 
     * @param array $config User configuration
     * @return array Complete configuration array
     */
    protected function loadConfig(): array {
        $configFile = __DIR__ . '/../config.php';
        
        if (file_exists($configFile)) {
            return require $configFile;
        }
        
        return [];
    }

    /**
     * Logs a message
     * 
     * @param string $message Message to log
     * @return void
     */
    public function log(string $message): void {
        if ($this->debug) {
            $logFile = ($this->config['paths']['logs'] ?? __DIR__ . '/storage/logs') . '/app.log';
            $dir = dirname($logFile);
            
            // Criar diretório se não existir
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            
            $timestamp = date('Y-m-d H:i:s');
            file_put_contents(
                $logFile,
                "[{$timestamp}] {$message}" . PHP_EOL,
                FILE_APPEND
            );
        }
    }

    /**
     * Sets up error and exception handling
     * 
     * @return void
     */
    protected function setupErrorHandling(): void {
        error_reporting(E_ALL);
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        
        if ($this->debug) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
        } else {
            ini_set('display_errors', 0);
            ini_set('display_startup_errors', 0);
        }
    }

    /**
     * Handles PHP errors
     * 
     * @param int $errno Error number
     * @param string $errstr Error message
     * @param string $errfile File where error occurred
     * @param int $errline Line number
     * @return bool Whether the error was handled
     */
    public function handleError($errno, $errstr, $errfile, $errline): bool {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $error = [
            'type' => $errno,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ];

        if ($this->debug) {
            throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        } else {
            error_log(json_encode($error));
            $this->view('errors/500', ['error' => $error]);
        }

        return true;
    }

    /**
     * Handles uncaught exceptions
     * 
     * @param \Throwable $e The exception
     * @return void
     */
    public function handleException(\Throwable $e): void {
        $error = [
            'type' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];

        if ($this->debug) {
            echo '<pre>';
            print_r($error);
            echo '</pre>';
        } else {
            error_log(json_encode($error));
            $this->view('errors/500', ['error' => $error]);
        }
    }

    /**
     * Registers a route handler
     *
     * @param string $route Route pattern
     * @param callable $handler Route handler function
     * @return self
     */
    public function on(string $route, callable $handler): self {
        // Separa método HTTP da rota
        if (strpos($route, ' ') !== false) {
            list($method, $path) = explode(' ', $route, 2);
            $route = $path;
            $method = strtoupper($method);
        } else {
            $method = 'GET';
        }
        
        // Adiciona o prefixo do grupo à rota e normaliza
        $route = $this->normalizeRoute($this->currentPrefix . $route);
        
        // Debug log
        if ($this->debug) {
            $this->log("Registering route: {$method} {$route}");
        }
        
        // Caso especial para a rota index
        if ($route === '/') {
            $pattern = '/^\\/?$/i';
        } else {
            // Caso especial para :any - deve vir antes da conversão normal de parâmetros
            if (strpos($route, ':any') !== false) {
                $pattern = str_replace(':any', '.*', $route);
            } else {
                // Converte parâmetros da rota para regex
                $pattern = preg_replace('/:[a-zA-Z]+/', '([^/]+)', $route);
            }
            
            $pattern = str_replace('/', '\/', $pattern);
            $pattern = '/^' . $pattern . '\/?$/i';
        }
        
        // Verifica se esta rota corresponde ao URL atual
        $uri = $this->normalizeRoute($_SERVER['REQUEST_URI']);
        $currentMethod = $_SERVER['REQUEST_METHOD'];
        
        // Debug log
        if ($this->debug) {
            $this->log("Checking route: {$currentMethod} {$uri} against pattern {$pattern}");
        }
        
        if (($method === $currentMethod || $method === 'ANY') && preg_match($pattern, $uri, $matches)) {
            array_shift($matches);
            
            try {
                ob_start();
                $handler = $handler->bindTo($this);
                call_user_func_array($handler, $matches);
                $output = ob_get_clean();
                
                if ($output) {
                    echo $output;
                }
                exit;
            } catch (\Throwable $e) {
                $this->handleException($e);
            }
        }
        
        // Se chegou aqui, verifica se é uma rota da API e executa o handler 404 apropriado
        if ($this->isRouteNotFound($uri)) {
            $this->handleNotFound($uri);
        }
        
        return $this;
    }

    /**
     * Normalizes a route pattern
     * 
     * @param string $route Route pattern
     * @return string Normalized route pattern
     */
    protected function normalizeRoute(string $route): string {
        // Remove query string se existir
        if (($pos = strpos($route, '?')) !== false) {
            $route = substr($route, 0, $pos);
        }
        
        // Converte para minúsculas
        $route = strtolower($route);
        
        // Garante que começa com /
        $route = '/' . ltrim($route, '/');
        
        // Remove barras duplicadas
        $route = preg_replace('#/+#', '/', $route);
        
        // Remove barra final exceto se for apenas /
        return $route === '/' ? '/' : rtrim($route, '/');
    }

    /**
     * Renders a view with data
     *
     * @param string $name View name/path
     * @param array $data Data to pass to the view
     * @throws \RuntimeException When view file not found
     * @return void
     */
    public function view(string $name, array $data = []): void {
        $file = ($this->config['paths']['views'] ?? __DIR__ . '/views') . '/' . $name . '.php';
        
        if (!file_exists($file)) {
            throw new \RuntimeException("View not found: {$name}");
        }
        
        extract($data);
        include $file;
    }

    /**
     * Sends plain text response
     *
     * @param string $content Response content
     * @param int $statusCode HTTP status code
     * @return void
     */
    public function text(string $content, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: text/plain; charset=UTF-8');
        echo $content;
    }

    public function json($data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    /**
     * Gets GET request parameter
     *
     * @param string|null $key Parameter key
     * @param mixed $default Default value if key not found
     * @return mixed Parameter value or default
     */
    public function get(?string $key = null, $default = null) {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    /**
     * Gets POST request parameter
     *
     * @param string|null $key Parameter key
     * @param mixed $default Default value if key not found
     * @return mixed Parameter value or default
     */
    public function post(?string $key = null, $default = null) {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }

    /**
     * Gets request parameter from GET or POST
     *
     * @param string|null $key Parameter key
     * @param mixed $default Default value if key not found
     * @return mixed Parameter value or default
     */
    public function request(?string $key = null, $default = null) {
        if ($key === null) {
            return $_REQUEST;
        }
        return $_REQUEST[$key] ?? $default;
    }

    public function input(?string $key = null, $default = null) {
        if ($key === null) {
            return array_merge($_GET, $_POST);
        }
        return $_REQUEST[$key] ?? $default;
    }

    /**
     * Gets or sets a header
     * 
     * @param string $key Header name
     * @param string|null $value Header value
     * @return string|array|null Header value or all headers
     */
    public function header(string $key, string $value = null): string|array|null {
        if ($value !== null) {
            header("$key: $value");
            return $value;
        }
        
        $headers = getallheaders();
        if ($key === null) {
            return $headers;
        }
        
        // Procura o header independente de maiúsculas/minúsculas
        $key = strtolower($key);
        foreach ($headers as $k => $v) {
            if (strtolower($k) === $key) {
                return $v;
            }
        }
        
        return null;
    }

    public function setHeaders(array $headers): self {
        foreach ($headers as $key => $value) {
            header("$key: $value");
        }
        return $this;
    }

    public function getHeaders(): array {
        return getallheaders();
    }

    public function hasHeader(string $key): bool {
        $headers = getallheaders();
        $key = strtolower($key);
        
        foreach ($headers as $k => $v) {
            if (strtolower($k) === $key) {
                return true;
            }
        }
        
        return false;
    }

    public function removeHeader(string $key): self {
        header_remove($key);
        return $this;
    }

    /**
     * Creates a route group
     * 
     * @param string $prefix Group prefix
     * @param callable $callback Group callback
     * @param callable|null $notFoundHandler Not found handler
     * @return self
     */
    public function group(string $prefix, callable $callback, ?callable $notFoundHandler = null): self {
        // Guardar o prefixo anterior
        $previousPrefix = $this->currentPrefix;
        
        // Adicionar o novo prefixo ao prefixo atual
        $this->currentPrefix .= $prefix;
        
        // Se tiver um handler de 404, registra para este prefixo
        if ($notFoundHandler) {
            $this->notFoundHandlers[$this->currentPrefix] = $notFoundHandler->bindTo($this);
        }
        
        // Executar o callback
        $callback();
        
        // Restaurar o prefixo anterior
        $this->currentPrefix = $previousPrefix;
        
        return $this;
    }

    /**
     * Checks if a route is not found
     * 
     * @param string $uri Request URI
     * @return bool Whether route is not found
     */
    protected function isRouteNotFound(string $uri): bool {
        static $checked = false;
        
        // Evita verificação múltipla para a mesma requisição
        if ($checked) {
            return false;
        }
        
        $checked = true;
        return true;
    }

    /**
     * Handles not found routes
     * 
     * @param string $uri Request URI
     * @return void
     */
    protected function handleNotFound(string $uri): void {
        // Encontra o handler mais específico para a URI atual
        $bestMatch = '';
        $handler = null;
        
        foreach ($this->notFoundHandlers as $prefix => $notFoundHandler) {
            if (strpos($uri, $prefix) === 0 && strlen($prefix) > strlen($bestMatch)) {
                $bestMatch = $prefix;
                $handler = $notFoundHandler;
            }
        }
        
        if ($handler) {
            $handler();
            exit;
        }
    }

    /**
     * Display setup required message
     */
    protected function showSetupRequired(): void {
        // Verifica se o framework já foi inicializado
        if (file_exists(__DIR__ . '/../.initialized')) {
            throw new \RuntimeException("Database configuration error");
        }

        $message = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initial Setup Required</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Initial Setup Required</h1>
            <p class="text-gray-600">The framework needs to be configured before it can be used.</p>
        </div>
        
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        The database is not properly configured.
                    </p>
                </div>
            </div>
        </div>
        
        <div class="bg-gray-50 rounded p-4 mb-6">
            <p class="text-gray-700 mb-4">Run the following command in your terminal to start the setup:</p>
            <div class="bg-gray-800 rounded p-3">
                <code class="text-green-400">./trindade init</code>
            </div>
        </div>
        
        <div class="text-sm text-gray-500">
            <p>For more information, check the <a href="docs/installation.md" class="text-blue-600 hover:text-blue-800">installation guide</a>.</p>
        </div>
    </div>
</body>
</html>
HTML;
        echo $message;
    }

    /**
     * Display database error message
     */
    protected function showDatabaseError(string $error): void {
        // Verifica se o framework já foi inicializado
        if (file_exists(__DIR__ . '/../.initialized')) {
            throw new \RuntimeException("Database connection error: " . $error);
        }

        // Limpa a mensagem de erro para ser mais amigável
        $errorMessage = $error;
        if (strpos($error, 'SQLSTATE[HY000] [2002]') !== false) {
            $errorMessage = 'Could not connect to MySQL server. Please verify if the server is running and the settings are correct.';
        } elseif (strpos($error, 'SQLSTATE[HY000] [1045]') !== false) {
            $errorMessage = 'MySQL authentication failed. Please check your username and password.';
        } elseif (strpos($error, 'SQLSTATE[HY000] [1049]') !== false) {
            $errorMessage = 'Database does not exist. Please check your database name.';
        }

        $message = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connection Error</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Database Connection Error</h1>
            <p class="text-gray-600">An error occurred while trying to connect to the database.</p>
        </div>
        
        <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700">
                        {$errorMessage}
                    </p>
                </div>
            </div>
        </div>
        
        <div class="bg-gray-50 rounded p-4 mb-6">
            <p class="text-gray-700 mb-4">To resolve this issue:</p>
            <ol class="list-decimal list-inside text-gray-600 space-y-2">
                <li>Verify if MySQL server is running</li>
                <li>Check your settings in config.php</li>
                <li>Run the command below to reconfigure:</li>
            </ol>
            <div class="bg-gray-800 rounded p-3 mt-4">
                <code class="text-green-400">./trindade init</code>
            </div>
        </div>
        
        <div class="text-sm text-gray-500">
            <p>For more information, check the <a href="docs/installation.md" class="text-blue-600 hover:text-blue-800">installation guide</a>.</p>
        </div>
    </div>
</body>
</html>
HTML;
        echo $message;
    }
} 