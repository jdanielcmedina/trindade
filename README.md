# Trindade Framework

Minimalist, secure PHP framework. **3 ficheiros**, zero camelCase, zero snake_case.

```
src/
├── Trindade.php   ← Database + Mail + Core (tudo num só ficheiro)
├── WebSocket.php  ← WebSocket server (Ratchet)
└── Installer.php  ← scaffolding
```

## Mail

```php
// com SMTP
$app = new \Trindade\Trindade([
    'mail' => [
        'host'     => 'smtp.example.com',
        'port'     => 587,
        'secure'   => 'tls',
        'username'  => 'user@example.com',
        'password'  => 'secret',
        'from'     => 'app@example.com',
        'name'     => 'My App',
    ],
]);

// sem SMTP (usa mail() do PHP)
$app->mail
    ->to('john@example.com')
    ->cc('jane@example.com')
    ->bcc('boss@example.com')
    ->from('app@example.com', 'My App')
    ->subject('Hello World')
    ->html('<h1>Hello</h1><p>Welcome!</p>')
    ->text('Hello. Welcome!')
    ->attach('/path/to/file.pdf', 'document.pdf')
    ->send();

// ou simples
$app->mail
    ->to('john@example.com')
    ->subject('Hi')
    ->message('Hello!')
    ->send();
```

## Database

```php
// Com config
$app = new \Trindade\Trindade([
    'db' => ['type' => 'mysql', 'host' => 'localhost', 'database' => 'db', 'username' => 'root', 'password' => ''],
]);

$app->db->select('users', '*', ['LIMIT' => 10]);
$app->db->get('users', '*', ['id' => 1]);
$app->db->insert('users', ['name' => 'John']);
$app->db->update('users', ['name' => 'Jane'], ['id' => 1]);
$app->db->delete('users', ['id' => 1]);
$app->db->count('users');
$app->db->has('users', ['email' => 'x@x.com']);
$app->db->max('users', 'age');
$app->db->min('users', 'age');
$app->db->avg('users', 'age');
$app->db->sum('users', 'salary');
```

## Tudo o resto

```php
// Routing
$app->on('GET /', fn() => ['ok' => true]);
$app->group('/api', fn() => ...);
$app->vhost('api.mydomain.com', fn() => ...);

// Session  → $app->session('key') / $app->session('key', 'val') / $app->session('key', null)
// Cookie   → $app->cookie('name') / $app->cookie('name', 'val') / $app->cookie('name', null)
// Cache    → $app->cache('key') / $app->cache('key', $data) / $app->cache('key', $data, 3600)
// CSRF     → $app->csrf() / $app->csrf(true) / $app->csrf_check()
// Password → $app->hash('secret') / $app->check('secret', $hash)
// Token    → $app->token()
// Upload   → $app->upload('photo')
// Download → $app->download('file.pdf')
// Random   → $app->random(32)
// Slug     → $app->slug('Hello World')
// Ago      → $app->ago('2024-01-01')
// Log      → $app->log('msg', 'error')
// View     → $app->view('home', $data)
// Layout   → $app->layout('home', 'default', $data)
// Partial  → $app->partial('header', $data)
// Validate → $app->validate(['email' => 'required|email'])
// Sanitize → $app->sanitize($input)
// Config   → $app->config('debug')
// Path     → $app->path('views')
// Storage  → $app->storage('uploads')
// Docs     → $app->docs()
// Import   → $app->import('https://api.example.com')
```

## WebSocket

```bash
php bin/ws start --port=8080
```

## Segurança (built-in, zero config)

- Headers: X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy
- Session: httponly, SameSite=Lax, secure
- CSRF: `$app->csrf()` / `$app->csrf_check()`
- Rate limit: `['security' => ['rate' => true]]`
- Upload: bloqueia .php, .phtml, etc.
- Path traversal bloqueado em views/downloads
- XSS: htmlspecialchars em tudo
- random_int() em vez de rand()
- Bcrypt cost 12
- debug = false por padrão

## Licença

MIT
