<?php
namespace Trindade;

/**
 * Trindade MCP Server
 *
 * Model Context Protocol — exposes Trindade as AI-callable tools over stdio.
 * Connect any MCP-compatible client (Claude Desktop, Cursor, etc).
 *
 *   php bin/mcp
 */
class Mcp
{
    private string $root;
    private ?Trindade $app = null;
    private array $tools;

    public function __construct()
    {
        $this->root = dirname(__DIR__);
        $this->tools = $this->define_tools();
    }

    public function serve(): void
    {
        $this->log('Trindade MCP starting');

        while ($line = fgets(STDIN)) {
            $msg = json_decode($line, true);
            if (!$msg) continue;

            $method = $msg['method'] ?? '';
            $id = $msg['id'] ?? null;

            try {
                $result = match ($method) {
                    'initialize'      => $this->handle_init($msg['params'] ?? []),
                    'tools/list'      => $this->handle_list(),
                    'tools/call'      => $this->handle_call($msg['params'] ?? []),
                    'resources/list'  => $this->handle_resources(),
                    'resources/read'  => $this->handle_resource_read($msg['params'] ?? []),
                    default           => throw new \Exception("Unknown method: {$method}"),
                };

                $this->write(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result]);
            } catch (\Throwable $e) {
                $this->write(['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => -32000, 'message' => $e->getMessage()]]);
            }
        }
    }

    private function app(): Trindade
    {
        if (!$this->app) {
            $config = [];
            $cf = $this->root . '/config.php';
            if (file_exists($cf)) $config = require $cf;
            $this->app = new Trindade(array_merge(['root' => $this->root, 'test' => true], $config));
        }
        return $this->app;
    }

    private function write(array $data): void
    {
        fwrite(STDOUT, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
        fflush(STDOUT);
    }

    private function log(string $msg): void
    {
        file_put_contents($this->root . '/storage/logs/mcp.log', date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
    }

    // ======================== MCP HANDLERS ========================

    private function handle_init(array $params): array
    {
        return [
            'protocolVersion' => '2024-11-05',
            'capabilities'    => ['tools' => [], 'resources' => []],
            'serverInfo'      => ['name' => 'trindade', 'version' => '2.0.0'],
        ];
    }

    private function handle_list(): array
    {
        return ['tools' => array_values($this->tools)];
    }

    private function handle_call(array $params): array
    {
        $name = $params['name'] ?? '';
        $args = $params['arguments'] ?? [];

        $tool = $this->tools[$name] ?? null;
        if (!$tool) throw new \Exception("Tool not found: {$name}");

        $fn = $tool['fn'];
        $result = $fn($args);

        return ['content' => [['type' => 'text', 'text' => is_string($result) ? $result : json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]]];
    }

    private function handle_resources(): array
    {
        $app = $this->app();
        $files = [];
        foreach (['routes', 'helpers', 'views'] as $d) {
            $p = $app->path($d);
            if ($p && is_dir($p)) {
                foreach (scandir($p) as $f) {
                    if (pathinfo($f, PATHINFO_EXTENSION) === 'php') {
                        $files[] = ['uri' => "file://{$d}/{$f}", 'name' => "{$d}/{$f}", 'mimeType' => 'text/x-php'];
                    }
                }
            }
        }
        return ['resources' => $files];
    }

    private function handle_resource_read(array $params): array
    {
        $uri = $params['uri'] ?? '';
        $app = $this->app();
        $path = str_replace('file://', '', $uri);
        $full = $app->path('root') . $path;
        if (!file_exists($full)) throw new \Exception("Resource not found: {$uri}");
        return ['contents' => [['uri' => $uri, 'mimeType' => 'text/x-php', 'text' => file_get_contents($full)]]];
    }

    // ======================== TOOL DEFINITIONS ========================

    private function define_tools(): array
    {
        return [
            // ── Routes ──
            'routes_list' => [
                'name' => 'routes_list',
                'description' => 'List all registered routes in the Trindade application.',
                'inputSchema' => ['type' => 'object', 'properties' => [], 'required' => []],
                'fn' => fn($args) => $this->tool_routes_list(),
            ],
            'routes_create' => [
                'name' => 'routes_create',
                'description' => 'Create a new route in the application. Adds the route to routes/web.php.',
                'inputSchema' => ['type' => 'object', 'properties' => [
                    'method' => ['type' => 'string', 'description' => 'HTTP method (GET, POST, PUT, DELETE, PATCH)'],
                    'path' => ['type' => 'string', 'description' => 'Route path, e.g. /users/:id'],
                    'code' => ['type' => 'string', 'description' => 'PHP code for the route handler. Use $app to access the framework.'],
                    'auth' => ['type' => 'string', 'description' => 'Authentication type: none, jwt, session, csrf'],
                ], 'required' => ['method', 'path', 'code']],
                'fn' => fn($args) => $this->tool_routes_create($args),
            ],
            'routes_delete' => [
                'name' => 'routes_delete',
                'description' => 'Delete a route from the application.',
                'inputSchema' => ['type' => 'object', 'properties' => [
                    'method' => ['type' => 'string'], 'path' => ['type' => 'string'],
                ], 'required' => ['method', 'path']],
                'fn' => fn($args) => $this->tool_routes_delete($args),
            ],
            'routes_validate' => [
                'name' => 'routes_validate',
                'description' => 'Validate PHP code for a route. Checks syntax and common security issues before deploying.',
                'inputSchema' => ['type' => 'object', 'properties' => [
                    'method' => ['type' => 'string'], 'code' => ['type' => 'string'], 'auth' => ['type' => 'string'],
                ], 'required' => ['code']],
                'fn' => fn($args) => $this->tool_routes_validate($args),
            ],
            'routes_code' => [
                'name' => 'routes_code',
                'description' => 'Get the source code of a specific route.',
                'inputSchema' => ['type' => 'object', 'properties' => [
                    'method' => ['type' => 'string'], 'path' => ['type' => 'string'],
                ], 'required' => ['method', 'path']],
                'fn' => fn($args) => $this->tool_routes_code($args),
            ],

            // ── Database ──
            'db_tables' => [
                'name' => 'db_tables',
                'description' => 'List all database tables.',
                'inputSchema' => ['type' => 'object', 'properties' => [], 'required' => []],
                'fn' => fn($args) => $this->tool_db_tables(),
            ],
            'db_query' => [
                'name' => 'db_query',
                'description' => 'Execute a raw SQL query. Returns array of rows. Use parameterized queries for safety.',
                'inputSchema' => ['type' => 'object', 'properties' => [
                    'sql' => ['type' => 'string', 'description' => 'SQL query to execute'],
                    'params' => ['type' => 'array', 'description' => 'Query parameters for prepared statement'],
                ], 'required' => ['sql']],
                'fn' => fn($args) => $this->tool_db_query($args),
            ],
            'db_schema' => [
                'name' => 'db_schema',
                'description' => 'Show the schema (columns, types) of a database table.',
                'inputSchema' => ['type' => 'object', 'properties' => [
                    'table' => ['type' => 'string'],
                ], 'required' => ['table']],
                'fn' => fn($args) => $this->tool_db_schema($args),
            ],
            'db_count' => [
                'name' => 'db_count',
                'description' => 'Count rows in a database table.',
                'inputSchema' => ['type' => 'object', 'properties' => [
                    'table' => ['type' => 'string'],
                    'where' => ['type' => 'object', 'description' => 'WHERE conditions, e.g. {"status":"active"}'],
                ], 'required' => ['table']],
                'fn' => fn($args) => $this->tool_db_count($args),
            ],

            // ── Files ──
            'files_read' => [
                'name' => 'files_read',
                'description' => 'Read the contents of a project file (routes, helpers, views, config).',
                'inputSchema' => ['type' => 'object', 'properties' => [
                    'path' => ['type' => 'string', 'description' => 'Relative path, e.g. routes/web.php'],
                ], 'required' => ['path']],
                'fn' => fn($args) => $this->tool_files_read($args),
            ],
            'files_write' => [
                'name' => 'files_write',
                'description' => 'Write content to a project file. Creates the file if needed.',
                'inputSchema' => ['type' => 'object', 'properties' => [
                    'path' => ['type' => 'string', 'description' => 'Relative path, e.g. routes/api.php'],
                    'content' => ['type' => 'string', 'description' => 'File content'],
                ], 'required' => ['path', 'content']],
                'fn' => fn($args) => $this->tool_files_write($args),
            ],
            'files_list' => [
                'name' => 'files_list',
                'description' => 'List project files in routes/, helpers/, views/, config.php.',
                'inputSchema' => ['type' => 'object', 'properties' => [], 'required' => []],
                'fn' => fn($args) => $this->tool_files_list(),
            ],

            // ── Logs ──
            'logs_view' => [
                'name' => 'logs_view',
                'description' => 'View application logs. Returns the most recent entries.',
                'inputSchema' => ['type' => 'object', 'properties' => [
                    'lines' => ['type' => 'integer', 'description' => 'Number of lines (default 50)'],
                    'level' => ['type' => 'string', 'description' => 'Filter by level: error, warning, info, debug'],
                ], 'required' => []],
                'fn' => fn($args) => $this->tool_logs_view($args),
            ],

            // ── Security ──
            'security_hash' => [
                'name' => 'security_hash',
                'description' => 'Hash a password using bcrypt (cost 12).',
                'inputSchema' => ['type' => 'object', 'properties' => [
                    'password' => ['type' => 'string'],
                ], 'required' => ['password']],
                'fn' => fn($args) => $this->tool_security_hash($args),
            ],
            'security_policy' => [
                'name' => 'security_policy',
                'description' => 'Check if a password meets the security policy requirements.',
                'inputSchema' => ['type' => 'object', 'properties' => [
                    'password' => ['type' => 'string'],
                ], 'required' => ['password']],
                'fn' => fn($args) => $this->tool_security_policy($args),
            ],
            'security_encrypt' => [
                'name' => 'security_encrypt',
                'description' => 'Encrypt data using AES-256-GCM.',
                'inputSchema' => ['type' => 'object', 'properties' => [
                    'data' => ['type' => 'string'],
                ], 'required' => ['data']],
                'fn' => fn($args) => $this->tool_security_encrypt($args),
            ],
            'security_decrypt' => [
                'name' => 'security_decrypt',
                'description' => 'Decrypt data that was encrypted with AES-256-GCM.',
                'inputSchema' => ['type' => 'object', 'properties' => [
                    'data' => ['type' => 'string'],
                ], 'required' => ['data']],
                'fn' => fn($args) => $this->tool_security_decrypt($args),
            ],
            'security_totp' => [
                'name' => 'security_totp',
                'description' => 'Generate a TOTP secret and current code, or verify a code against a secret.',
                'inputSchema' => ['type' => 'object', 'properties' => [
                    'secret' => ['type' => 'string', 'description' => 'Optional secret to generate code for. If omitted, generates new secret.'],
                    'code' => ['type' => 'string', 'description' => 'Optional code to verify against secret.'],
                ], 'required' => []],
                'fn' => fn($args) => $this->tool_security_totp($args),
            ],

            // ── Config ──
            'config_get' => [
                'name' => 'config_get',
                'description' => 'Read the application configuration (sensitive values are masked).',
                'inputSchema' => ['type' => 'object', 'properties' => [
                    'key' => ['type' => 'string', 'description' => 'Specific config key to read'],
                ], 'required' => []],
                'fn' => fn($args) => $this->tool_config_get($args),
            ],

            // ── Supervisor ──
            'supervisor' => [
                'name' => 'supervisor',
                'description' => 'Health check and system metrics: uptime, memory, routes, DB status, logs summary.',
                'inputSchema' => ['type' => 'object', 'properties' => [], 'required' => []],
                'fn' => fn($args) => $this->tool_supervisor(),
            ],

            // ── Users ──
            'user_create' => [
                'name' => 'user_create',
                'description' => 'Create a new user in the database (hashes password with bcrypt).',
                'inputSchema' => ['type' => 'object', 'properties' => [
                    'email' => ['type' => 'string'], 'password' => ['type' => 'string'],
                    'name' => ['type' => 'string'], 'role' => ['type' => 'string'],
                ], 'required' => ['email', 'password']],
                'fn' => fn($args) => $this->tool_user_create($args),
            ],
            'user_delete' => [
                'name' => 'user_delete',
                'description' => 'Delete a user by ID or email.',
                'inputSchema' => ['type' => 'object', 'properties' => [
                    'identifier' => ['type' => 'string', 'description' => 'User ID or email'],
                ], 'required' => ['identifier']],
                'fn' => fn($args) => $this->tool_user_delete($args),
            ],
            'user_list' => [
                'name' => 'user_list',
                'description' => 'List users with optional filter.',
                'inputSchema' => ['type' => 'object', 'properties' => [
                    'where' => ['type' => 'object', 'description' => 'Filter conditions, e.g. {"role":"admin"}'],
                    'limit' => ['type' => 'integer'],
                ], 'required' => []],
                'fn' => fn($args) => $this->tool_user_list($args),
            ],
            'user_verify' => [
                'name' => 'user_verify',
                'description' => 'Verify user credentials. Returns user data (without password) or null.',
                'inputSchema' => ['type' => 'object', 'properties' => [
                    'email' => ['type' => 'string'], 'password' => ['type' => 'string'],
                ], 'required' => ['email', 'password']],
                'fn' => fn($args) => $this->tool_user_verify($args),
            ],

            // ── DB Connections ──
            'db_connections' => [
                'name' => 'db_connections',
                'description' => 'List all active database connections.',
                'inputSchema' => ['type' => 'object', 'properties' => [], 'required' => []],
                'fn' => fn($args) => $this->tool_db_connections(),
            ],

            // ── Queue ──
            'queue_push' => [
                'name' => 'queue_push',
                'description' => 'Push a job to the queue for async processing.',
                'inputSchema' => ['type' => 'object', 'properties' => [
                    'job' => ['type' => 'string', 'description' => 'Job name/identifier'],
                    'payload' => ['type' => 'object', 'description' => 'Job data'],
                    'delay' => ['type' => 'integer', 'description' => 'Delay in seconds before processing'],
                ], 'required' => ['job']],
                'fn' => fn($args) => $this->tool_queue_push($args),
            ],
            'queue_status' => [
                'name' => 'queue_status',
                'description' => 'Get queue status: pending and failed jobs count.',
                'inputSchema' => ['type' => 'object', 'properties' => [], 'required' => []],
                'fn' => fn($args) => $this->tool_queue_status(),
            ],
            'queue_retry' => [
                'name' => 'queue_retry',
                'description' => 'Retry all failed jobs.',
                'inputSchema' => ['type' => 'object', 'properties' => [], 'required' => []],
                'fn' => fn($args) => $this->tool_queue_retry(),
            ],
            'schedule_list' => [
                'name' => 'schedule_list',
                'description' => 'List scheduled tasks.',
                'inputSchema' => ['type' => 'object', 'properties' => [], 'required' => []],
                'fn' => fn($args) => $this->tool_schedule_list(),
            ],

            // ── Cache / Storage ──
            'cache_clear' => [
                'name' => 'cache_clear',
                'description' => 'Clear the application cache.',
                'inputSchema' => ['type' => 'object', 'properties' => [], 'required' => []],
                'fn' => fn($args) => $this->tool_cache_clear(),
            ],
            'backup_create' => [
                'name' => 'backup_create',
                'description' => 'Create a backup of the application (database + files).',
                'inputSchema' => ['type' => 'object', 'properties' => [
                    'type' => ['type' => 'string', 'description' => 'Backup type: full, db, files'],
                ], 'required' => []],
                'fn' => fn($args) => $this->tool_backup_create($args),
            ],

            // ── System ──
            'system_info' => [
                'name' => 'system_info',
                'description' => 'Get system information: PHP version, routes count, DB status, storage usage.',
                'inputSchema' => ['type' => 'object', 'properties' => [], 'required' => []],
                'fn' => fn($args) => $this->tool_system_info(),
            ],
            'jwt_generate' => [
                'name' => 'jwt_generate',
                'description' => 'Generate a JWT token with a given payload.',
                'inputSchema' => ['type' => 'object', 'properties' => [
                    'payload' => ['type' => 'object', 'description' => 'JWT payload, e.g. {"user_id":1,"role":"admin"}'],
                    'expire' => ['type' => 'integer', 'description' => 'Expiration in seconds (default 3600)'],
                ], 'required' => ['payload']],
                'fn' => fn($args) => $this->tool_jwt_generate($args),
            ],
        ];
    }

    // ======================== TOOL IMPLEMENTATIONS ========================

    private function tool_routes_list(): string
    {
        $app = $this->app();
        $routes = $app->routes();
        $list = [];
        foreach ($routes as $method => $rs) {
            foreach ($rs as $path => $info) {
                $vhost = !empty($info['vhost']) ? " [{$info['vhost']}]" : '';
                $list[] = "{$method} {$path}{$vhost}";
            }
        }
        return json_encode(['count' => count($list), 'routes' => $list]);
    }

    private function tool_routes_create(array $args): string
    {
        $method = $args['method'] ?? 'GET';
        $path = $args['path'] ?? '/';
        $code = $args['code'] ?? '';
        $auth = $args['auth'] ?? 'none';

        $app = $this->app();
        $file = $app->path('routes') . '/web.php';
        $line = "\n\$app->on('{$method} {$path}', function () use (\$app) {\n";
        if ($auth === 'csrf') $line .= "    if (!\$app->csrf_check()) return \$app->error('CSRF', 403);\n";
        if ($auth === 'jwt') $line .= "    if (!\$app->jwt(\$app->token())) return \$app->error('Unauthorized', 401);\n";
        if ($auth === 'session') $line .= "    if (!\$app->session('user_id')) return \$app->error('Unauthorized', 401);\n";
        $line .= "    {$code}\n";
        $line .= "});\n";
        file_put_contents($file, file_get_contents($file) . $line);
        return "Route {$method} {$path} created successfully.";
    }

    private function tool_routes_delete(array $args): string
    {
        $method = $args['method'];
        $path = $args['path'];
        $app = $this->app();
        $file = $app->path('routes') . '/web.php';
        $c = file_get_contents($file);
        $em = preg_quote($method, '/');
        $ep = preg_quote($path, '/');
        $c = preg_replace("/\\\$app->on\\('{$em}\\s+{$ep}',\\s*function\\s*\\([^)]*\\)\\s*use\\s*\\([^)]*\\)\\s*\\{[^}]*\\}\\);/s", '', $c);
        file_put_contents($file, $c);
        return "Route {$method} {$path} deleted.";
    }

    private function tool_routes_validate(array $args): string
    {
        $method = $args['method'] ?? 'GET';
        $code = $args['code'] ?? '';
        $auth = $args['auth'] ?? 'none';

        $tmp = tempnam(sys_get_temp_dir(), 'tr_') . '.php';
        file_put_contents($tmp, "<?php\n" . $code . "\n?>");
        exec("php -l " . escapeshellarg($tmp) . " 2>&1", $out, $exit);
        unlink($tmp);

        $warnings = [];
        if (in_array($method, ['POST','PUT','DELETE']) && $auth === 'none') $warnings[] = "Route {$method} sem autenticacao ou CSRF";
        if (preg_match('/\$app->db/', $code) && $auth === 'none') $warnings[] = "Operacoes DB sem autenticacao";
        if (!preg_match('/return/', $code)) $warnings[] = "Nenhum return encontrado";

        return json_encode([
            'syntax_ok' => $exit === 0,
            'warnings' => $warnings,
            'ready' => $exit === 0 && empty($warnings),
            'output' => implode("\n", $out),
        ]);
    }

    private function tool_routes_code(array $args): string
    {
        $method = $args['method'];
        $path = $args['path'];
        $app = $this->app();
        $file = $app->path('routes') . '/web.php';
        $c = file_get_contents($file);
        $em = preg_quote($method, '/');
        $ep = preg_quote($path, '/');
        if (preg_match("/\\\$app->on\\('{$em}\\s+{$ep}',\\s*(function\\s*\\([^)]*\\)\\s*use\\s*\\([^)]*\\)\\s*\\{[^}]*\\})\\s*\\);/s", $c, $m)) {
            return $m[1];
        }
        return 'Route source not found.';
    }

    private function tool_db_tables(): string
    {
        $app = $this->app();
        if (!$app->db) return 'No database configured.';
        try { $t = $app->db->query("SHOW TABLES")->fetchAll(\PDO::FETCH_NUM); return json_encode(array_map(fn($r) => $r[0], $t)); }
        catch (\Throwable $e) { return "Error: {$e->getMessage()}"; }
    }

    private function tool_db_query(array $args): string
    {
        $app = $this->app();
        if (!$app->db) return 'No database configured.';
        try {
            $stmt = $app->db->query($args['sql'], $args['params'] ?? []);
            return json_encode($stmt->fetchAll(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) { return "Error: {$e->getMessage()}"; }
    }

    private function tool_db_schema(array $args): string
    {
        $app = $this->app();
        if (!$app->db) return 'No database configured.';
        try { $r = $app->db->query("DESCRIBE " . $args['table'])->fetchAll(); return json_encode($r, JSON_PRETTY_PRINT); }
        catch (\Throwable $e) { return "Error: {$e->getMessage()}"; }
    }

    private function tool_db_count(array $args): string
    {
        $app = $this->app();
        if (!$app->db) return 'No database configured.';
        try { return (string)$app->db->count($args['table'], $args['where'] ?? []); }
        catch (\Throwable $e) { return "Error: {$e->getMessage()}"; }
    }

    private function tool_files_read(array $args): string
    {
        $app = $this->app();
        $path = $args['path'];
        $allowed = ['routes', 'helpers', 'views'];
        $dir = explode('/', $path)[0];
        if (!in_array($dir, $allowed) && $path !== 'config.php') throw new \Exception("Access denied: {$path}");
        $full = $app->path('root') . $path;
        if (!file_exists($full)) return "File not found: {$path}";
        return file_get_contents($full);
    }

    private function tool_files_write(array $args): string
    {
        $app = $this->app();
        $path = $args['path'];
        $full = $app->path('root') . $path;
        $dir = dirname($full);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        file_put_contents($full, $args['content']);
        return "File written: {$path} (" . strlen($args['content']) . " bytes)";
    }

    private function tool_files_list(): string
    {
        $app = $this->app();
        $list = [];
        foreach (['routes', 'helpers', 'views'] as $d) {
            $p = $app->path($d);
            if ($p && is_dir($p)) {
                foreach (scandir($p) as $f) {
                    if ($f !== '.' && $f !== '..' && $f !== '.gitkeep') $list[] = "{$d}/{$f}";
                }
            }
        }
        if (file_exists($app->path('root') . 'config.php')) $list[] = 'config.php';
        return json_encode($list, JSON_PRETTY_PRINT);
    }

    private function tool_logs_view(array $args): string
    {
        $app = $this->app();
        $lines = (int)($args['lines'] ?? 50);
        $level = $args['level'] ?? null;
        $log = $app->storage('logs') . '/app.log';
        if (!file_exists($log)) return 'No logs.';
        $all = array_reverse(array_filter(explode("\n", file_get_contents($log))));
        if ($level) $all = array_filter($all, fn($l) => stripos($l, "[{$level}]") !== false);
        return implode("\n", array_slice($all, 0, $lines));
    }

    private function tool_security_hash(array $args): string
    {
        $app = $this->app();
        return $app->hash($args['password']);
    }

    private function tool_security_policy(array $args): string
    {
        $app = $this->app();
        $errors = $app->password_policy($args['password']);
        return empty($errors) ? 'Password meets policy.' : json_encode(['errors' => $errors]);
    }

    private function tool_security_encrypt(array $args): string
    {
        $app = $this->app();
        return $app->encrypt($args['data']);
    }

    private function tool_security_decrypt(array $args): string
    {
        $app = $this->app();
        return $app->decrypt($args['data']) ?? 'Decryption failed.';
    }

    private function tool_security_totp(array $args): string
    {
        $app = $this->app();
        $secret = $args['secret'] ?? null;
        $code = $args['code'] ?? null;

        if ($secret === null) {
            $s = $app->totp();
            return json_encode(['secret' => $s, 'current_code' => $app->totp($s)]);
        }
        if ($code === null) {
            return json_encode(['secret' => $secret, 'current_code' => $app->totp($secret)]);
        }
        return $app->totp($secret, $code) ? 'Code is valid.' : 'Code is invalid.';
    }

    private function tool_config_get(array $args): string
    {
        $app = $this->app();
        $config = $app->config();
        if ($key = ($args['key'] ?? null)) return json_encode($config[$key] ?? null, JSON_PRETTY_PRINT);

        // Mask sensitive values
        foreach (['password', 'secret', 'app_key', 'username'] as $sk) {
            if (isset($config['db'][$sk])) $config['db'][$sk] = '***';
            if (isset($config['mail'][$sk])) $config['mail'][$sk] = '***';
            if (isset($config['jwt'][$sk])) $config['jwt'][$sk] = '***';
        }
        return json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function tool_cache_clear(): string
    {
        $app = $this->app();
        $app->clear('cache');
        return 'Cache cleared.';
    }

    private function tool_backup_create(array $args): string
    {
        $app = $this->app();
        $type = $args['type'] ?? 'full';
        $file = $app->backup($type);
        return $file ? "Backup created: {$file}" : 'Backup failed.';
    }

    private function tool_system_info(): string
    {
        $app = $this->app();
        $routes = 0; foreach ($app->routes() as $rs) $routes += count($rs);
        $dbOk = $app->db !== null;
        return json_encode(['php' => PHP_VERSION, 'routes' => $routes, 'database' => $dbOk, 'debug' => $app->config('debug')]);
    }

    private function tool_jwt_generate(array $args): string
    {
        $app = $this->app();
        return $app->jwt($args['payload'], $args['expire'] ?? null);
    }

    private function tool_supervisor(): string
    {
        return json_encode($this->app()->supervisor(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function tool_user_create(array $args): string
    {
        $result = $this->app()->user_create($args);
        return $result ? "User created. ID: {$result}" : "Failed to create user (duplicate email or missing data).";
    }

    private function tool_user_delete(array $args): string
    {
        $this->app()->user_delete($args['identifier']);
        return "User deleted: {$args['identifier']}";
    }

    private function tool_user_list(array $args): string
    {
        $users = $this->app()->user_list($args['where'] ?? [], $args['limit'] ?? 50);
        return json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function tool_user_verify(array $args): string
    {
        $user = $this->app()->user_verify($args['email'], $args['password']);
        return $user ? json_encode($user, JSON_PRETTY_PRINT) : 'Invalid credentials.';
    }

    private function tool_db_connections(): string
    {
        return json_encode($this->app()->db_connections());
    }

    private function tool_queue_push(array $args): string
    {
        $this->app()->queue($args['job'], $args['payload'] ?? [], $args['delay'] ?? 0);
        $s = $this->app()->queue_status();
        return "Job pushed. Queue: {$s['pending']} pending, {$s['failed']} failed.";
    }

    private function tool_queue_status(): string
    {
        return json_encode($this->app()->queue_status());
    }

    private function tool_queue_retry(): string
    {
        $n = $this->app()->queue_retry();
        return "{$n} failed jobs requeued.";
    }

    private function tool_schedule_list(): string
    {
        $dir = $this->app()->storage('cache') . '/schedule/';
        if (!is_dir($dir)) return 'No scheduled tasks.';
        $list = [];
        foreach (glob($dir . '*') as $f) {
            $t = unserialize(file_get_contents($f));
            if ($t) $list[] = $t['cron'];
        }
        return json_encode($list);
    }
}
