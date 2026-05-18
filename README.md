# Trindade Framework

**1 ficheiro. Tudo built-in.** Secure, minimalist PHP framework.

```
src/Trindade.php   ← Database + Mail + WebSocket + JWT + QR + CSRF + Middleware
```

## Quick Start

```bash
composer create-project jdanielcmedina/trindade my-project
cd my-project
composer install
php trindade serve
```

Open `http://localhost:8000`.

## Studio — Visual IDE

```bash
php trindade studio
```

Open `http://localhost:8000/studio`. Password: `trindade`.

| Page | Does |
|---|---|
| Dashboard | Stats, PHP version, routes, storage |
| Routes | Create/edit/delete routes via browser |
| Database | Browse tables, SQL console |
| Files | Edit routes/, helpers/, views/ files |
| API Console | Send requests, see responses |
| Logs | Live log viewer |

Add to `config.php`: `'studio' => ['password' => 'your-secret']`.

## CLI

| Command | Does |
|---|---|
| `php trindade routes` | List all routes |
| `php trindade cache:clear` | Clear storage |
| `php trindade key:generate` | Generate APP_KEY |
| `php trindade serve` | Dev server |
| `php trindade studio` | Studio info |
| `php trindade make:password` | Hash password |
| `php trindade make:jwt '{...}'` | Generate JWT |
| `php trindade env:check` | Check extensions |

## API (all `$app->...`)

### Database
```php
$app->db->select('users', '*', ['LIMIT' => 10]);
$app->db->get('users', '*', ['id' => 1]);
$app->db->insert('users', ['name' => 'John']);
$app->db->update('users', ['name' => 'Jane'], ['id' => 1]);
$app->db->delete('users', ['id' => 1]);
$app->db->count('users');
$app->db->has('users', ['email' => 'x@x.com']);
$app->db->pages('users', 10, 1);
```

### Mail (fluent)
```php
$app->mail->to('x@x.com')->subject('Hi')->html('<h1>Hello</h1>')->send();
$app->mail->to('x@x.com')->cc('y@y.com')->attach('file.pdf')->send();
```

### JWT
```php
$token = $app->jwt(['user' => 1]);
$data  = $app->jwt($token); // decode + verify
```

### QR Code
```php
$img = $app->qr('https://example.com');  // base64 data URI
$app->qr('hello', 300, 'qr.png');        // save to file
```

### WebSocket
```bash
php bin/ws start --port=8080
```

```php
$ws->on('message', fn($d) => $ws->broadcast('chat', $d['data']));
$ws->channel('room', 'msg', ['text' => 'Hi']);
```

### Middleware
```php
$app->use(function ($next) use ($app) {
    $app->log($_SERVER['REQUEST_URI']);
    return $next();
});
```

### Session / Cookie / CSRF / Password
```php
$app->session('key')        / $app->session('key', 'val')  / $app->session('key', null)
$app->cookie('name')        / $app->cookie('name', 'val')  / $app->cookie('name', null)
$app->csrf()                / $app->csrf(true)             / $app->csrf_check()
$app->hash('secret')        / $app->check('secret', $hash)
```

### Everything else
```php
$app->upload('file')      $app->download('file.pdf')      $app->view('home', $d)
$app->layout('x','y',$d)  $app->partial('header', $d)     $app->cache('k',$v,60)
$app->random(32)          $app->slug('Hello')             $app->ago('2024-01-01')
$app->docs()              $app->import('https://api.ex')  $app->validate([...])
$app->sanitize($input)    $app->log('msg','error')         $app->token()
```

## Security (built-in, zero config)

- Headers: X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy
- Session: httponly, SameSite=Lax, secure
- CSRF, rate limiting, bcrypt cost 12, random_int()
- Upload: blocks .php, .phtml, etc. Path traversal blocked
- XSS: htmlspecialchars everywhere. debug=false by default.

## Structure

```
my-project/
├── public/index.php      ← ONLY web-accessible file
├── config.php            ← credentials (outside public/)
├── routes/web.php        ← route definitions
├── views/                ← templates
├── plugins/              ← Studio and custom plugins
├── storage/              ← cache, logs, uploads (never exposed)
├── helpers/
└── vendor/
```

MIT
