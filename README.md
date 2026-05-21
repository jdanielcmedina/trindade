# Trindade Framework

**1 ficheiro. Tudo built-in.** Secure, minimalist PHP framework.

```
src/Trindade.php   ← Database + Mail + WebSocket + JWT + QR + CSRF + Events + Queue + Scheduler
```

## MCP — AI-Native

O Trindade expõe 33 ferramentas via MCP (Model Context Protocol) para Claude, Cursor, etc.

```bash
git clone https://github.com/jdanielcmedina/trindade.git my-project
cd my-project
composer install
```

Configura o Claude Desktop com o `mcp.json` do repo:

```json
{
  "mcpServers": {
    "trindade": {
      "command": "php",
      "args": ["bin/mcp"],
      "cwd": "/caminho/para/my-project"
    }
  }
}
```

A IA pode: criar rotas, validar código, correr queries SQL, ver logs, gerir utilizadores, encriptar dados, fazer backups — tudo sem abrir o browser.

| Tool | Descrição |
|---|---|
| `routes_create` / `routes_delete` / `routes_validate` | Gerir rotas com validação |
| `db_query` / `db_schema` / `db_tables` / `db_count` | Base de dados |
| `user_create` / `user_delete` / `user_list` / `user_verify` | Gestão de users |
| `security_hash` / `security_encrypt` / `security_totp` | Segurança |
| `supervisor` | Health check (uptime, memory, DB status) |
| `queue_push` / `queue_status` / `queue_retry` | Jobs assíncronos |
| `logs_view` | Logs com filtro |
| `files_read` / `files_write` | Editar ficheiros do projeto |
| `backup_create` / `cache_clear` / `system_info` | DevOps |
| `jwt_generate` | Gerar tokens JWT |

## Quick Start

```bash
composer create-project jdanielcmedina/trindade my-project
cd my-project
composer install
php trindade serve
```

`http://localhost:8000` — API a correr.

## Database (Medoo-compatible)

```php
$app->db->select('users', ['username' => 'name', 'years' => 'age'], ['status' => 'active', 'LIMIT' => 10, 'ORDER' => ['id' => 'DESC']]);
$app->db->get('users', '*', ['id' => 1]);
$app->db->count('users', '*', ['role' => 'admin']);
$app->db->has('users', ['email' => 'x@x.com']);
$app->db->pages('users', 10, 1);

// JOINs
$app->db->select('users', ['[>]profiles' => ['user_id' => 'id']], '*');
$app->db->select('users', ['[><]roles' => ['role_id' => 'id']], '*');

// Todos os operadores: [>] [<] [>=] [<=] [!] [~] [!~] [<>] [><] IN NOT IN IS NULL
// Colunas com alias: ['apelido' => 'name', 'idade' => 'age']
// Raw: ['age[>]' => Database::raw('NOW()')]
```

## Múltiplas Bases de Dados

```php
$app = new Trindade([
    'databases' => [
        'main'   => ['type' => 'mysql', ...],
        'logs'   => ['type' => 'sqlite', 'database' => 'logs.db'],
    ],
]);
$app->db->select('users', '*');         // main
$app->db('logs')->select('events', '*'); // logs
```

## Mail

```php
$app->mail->to('x@x.com')->cc('y@y.com')->subject('Hi')->html('<h1>Hello</h1>')->send();
$app->mail->to('x@x.com')->attach('file.pdf')->send();
```

## Event System

```php
$app->listen('user.created', function ($data) use ($app) {
    $app->mail->to($data['email'])->subject('Welcome')->send();
});
$app->emit('user.created', $data);
```

## Queue + Scheduler

```php
$app->queue('send-email', ['to' => 'x@x.com']);
$app->schedule('daily', fn() => $app->backup());

// Worker
php bin/worker queue      # processa jobs
php bin/worker schedule   # corre scheduler
php bin/worker all        # ambos em loop
```

## CLI

| Comando | Descrição |
|---|---|
| `php trindade serve` | Dev server |
| `php bin/mcp` | MCP server |
| `php bin/worker queue` | Processa jobs |
| `php bin/ws start` | WebSocket server |
| `php trindade key:generate` | APP_KEY |
| `php trindade make:password` | Hash password |
| `php trindade env:check` | Extensões PHP |

## Segurança (built-in)

Headers automáticos, CSRF, rate limiting, bcrypt cost 12, random_int(), upload seguro, path traversal bloqueado, debug=false por defeito.

## Licença

MIT
