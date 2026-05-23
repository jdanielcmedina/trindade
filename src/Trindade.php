<?php
namespace Trindade;

/**
 * Trindade Resource — consistent API response formatting.
 *
 * $app->resource($user)->only('id', 'name', 'email');
 * $app->resource($users)->collection()->transform(fn($u) => [...]);
 */
class Resource
{
    private $data;
    private bool $isCollection = false;

    public function __construct($data) { $this->data = $data; }

    public function collection(): self { $this->isCollection = true; return $this; }
    public function only(string ...$keys): array { return $this->transform(fn($item) => array_intersect_key((array)$item, array_flip($keys))); }
    public function except(string ...$keys): array { return $this->transform(fn($item) => array_diff_key((array)$item, array_flip($keys))); }
    public function add(array $extra): array { return $this->transform(fn($item) => array_merge((array)$item, $extra)); }

    public function transform(callable $fn): array
    {
        if ($this->isCollection) return array_map(fn($item) => $fn((array)$item), (array)$this->data);
        return $fn((array)$this->data);
    }

    public function paginate(int $total, int $page = 1, int $perPage = 10): array
    {
        return [
            'data'  => $this->data,
            'meta'  => ['total' => $total, 'page' => $page, 'per_page' => $perPage, 'pages' => (int)ceil($total / max($perPage, 1))],
        ];
    }
}

/**
 * Trindade Collection
 *
 * Fluent array wrapper. Chain methods to transform data.
 *
 * collect($rows)->pluck('email')->unique()->values()->all();
 */
class Collection implements \ArrayAccess, \IteratorAggregate, \Countable
{
    private array $items;

    public function __construct(array $items = []) { $this->items = $items; }

    public static function make(array $items = []): self { return new self($items); }

    public function all(): array { return $this->items; }
    public function toArray(): array { return $this->items; }
    public function count(): int { return count($this->items); }
    public function isEmpty(): bool { return empty($this->items); }
    public function isNotEmpty(): bool { return !empty($this->items); }
    public function first($default = null) { return $this->items[0] ?? $default; }
    public function last($default = null) { $c = count($this->items); return $c > 0 ? $this->items[$c-1] : $default; }

    public function map(callable $fn): self { return new self(array_map($fn, $this->items)); }
    public function filter(?callable $fn = null): self { return new self(array_filter($this->items, $fn ?? fn($v) => !empty($v))); }
    public function pluck(string $key): self { return new self(array_column($this->items, $key)); }
    public function unique(): self { return new self(array_unique($this->items, SORT_REGULAR)); }
    public function values(): self { return new self(array_values($this->items)); }
    public function keys(): self { return new self(array_keys($this->items)); }
    public function take(int $n): self { return new self(array_slice($this->items, 0, $n)); }
    public function skip(int $n): self { return new self(array_slice($this->items, $n)); }
    public function sort(?callable $fn = null): self { $items = $this->items; $fn ? usort($items, $fn) : sort($items); return new self($items); }
    public function sortBy(string $key, bool $desc = false): self { $items = $this->items; usort($items, fn($a, $b) => ($a[$key] ?? 0) <=> ($b[$key] ?? 0)); return new self($desc ? array_reverse($items) : $items); }
    public function sortByDesc(string $key): self { return $this->sortBy($key, true); }
    public function groupBy(string $key): self { $r = []; foreach ($this->items as $v) $r[$v[$key] ?? ''][] = $v; return new self($r); }
    public function sum(?string $key = null): float|int { return $key ? array_sum(array_column($this->items, $key)) : array_sum($this->items); }
    public function avg(?string $key = null): float { $c = count($this->items); return $c ? ($this->sum($key) / $c) : 0; }
    public function implode(string $glue, ?string $key = null): string { return $key ? implode($glue, array_column($this->items, $key)) : implode($glue, $this->items); }
    public function flatten(): self { $r = []; array_walk_recursive($this->items, fn($v) => $r[] = $v); return new self($r); }
    public function each(callable $fn): self { foreach ($this->items as $k => $v) $fn($v, $k); return $this; }
    public function reduce(callable $fn, $initial = null) { return array_reduce($this->items, $fn, $initial); }
    public function where(string $key, $value): self { return new self(array_filter($this->items, fn($v) => ($v[$key] ?? null) === $value)); }
    public function whereIn(string $key, array $values): self { return new self(array_filter($this->items, fn($v) => in_array($v[$key] ?? null, $values, true))); }

    // ArrayAccess
    public function offsetExists(mixed $offset): bool { return isset($this->items[$offset]); }
    public function offsetGet(mixed $offset): mixed { return $this->items[$offset] ?? null; }
    public function offsetSet(mixed $offset, mixed $value): void { if ($offset === null) $this->items[] = $value; else $this->items[$offset] = $value; }
    public function offsetUnset(mixed $offset): void { unset($this->items[$offset]); }

    // IteratorAggregate
    public function getIterator(): \ArrayIterator { return new \ArrayIterator($this->items); }

    // JSON
    public function toJson(): string { return json_encode($this->items, JSON_UNESCAPED_UNICODE); }
    public function __toString(): string { return $this->toJson(); }
}

/**
 * Trindade Database
 *
 * Full Medoo-compatible PDO wrapper. Zero external dependency.
 * Supports ALL Medoo expressions and operators.
 *
 * Access via $app->db->select(...), $app->db->get(...), etc.
 */
class Database
{
    private \PDO $pdo;
    private string $type;
    private array $log = [];
    private bool $logging = false;
    private $lastError;

    public function __construct(array $config)
    {
        $this->type = $config['type'] ?? 'mysql';

        $dsn = match ($this->type) {
            'sqlite' => 'sqlite:' . $config['database'],
            'pgsql'  => "pgsql:host={$config['host']};dbname={$config['database']};port=" . ($config['port'] ?? 5432),
            'mssql'  => "dblib:host={$config['host']};dbname={$config['database']}",
            'sybase' => "sybase:host={$config['host']};dbname={$config['database']}",
            default  => "mysql:host={$config['host']};dbname={$config['database']};port=" . ($config['port'] ?? 3306) . ";charset=" . ($config['charset'] ?? 'utf8mb4'),
        };

        $this->pdo = new \PDO($dsn, $config['username'] ?? null, $config['password'] ?? null, [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    // ======================== RAW ========================

    /**
     * Create a raw SQL expression (not parameterized).
     *
     * $app->db->select('users', '*', ['age[>]' => Database::raw('NOW()')])
     */
    public static function raw(string $sql): \stdClass
    {
        $obj = new \stdClass();
        $obj->value = $sql;
        return $obj;
    }

    // ======================== QUERY ========================

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $start = microtime(true);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $elapsed = round((microtime(true) - $start) * 1000, 2);

        if ($this->logging) $this->log[] = ['sql' => $sql, 'params' => $params, 'ms' => $elapsed];
        return $stmt;
    }

    public function exec(string $sql, array $params = []): int
    {
        $start = microtime(true);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $elapsed = round((microtime(true) - $start) * 1000, 2);

        if ($this->logging) $this->log[] = ['sql' => $sql, 'params' => $params, 'ms' => $elapsed];
        return $stmt->rowCount();
    }

    // ======================== SELECT ========================

    /**
     * Select multiple rows. Supports JOINs in $where.
     *
     * $app->db->select('users', '*')
     * $app->db->select('users', ['id', 'name'], ['status' => 1, 'LIMIT' => 10])
     * $app->db->select('users', ['[>]profiles' => ['user_id' => 'id']], '*', ['status' => 1])
     */
    public function select(string $table, $columns, $where = null): array
    {
        if (func_num_args() === 2) {
            $where = [];
        } elseif ($where === null) {
            $where = [];
        }

        $join = null;
        if (is_array($columns) && !empty($columns)) {
            $firstKey = array_key_first($columns);
            if (is_string($firstKey) && preg_match('/^\[.+\]/', $firstKey)) {
                $join = $columns;
                $columns = $where ?? '*';
                $where = func_num_args() >= 4 ? func_get_arg(3) : [];
            }
        }

        $cols = $this->cols($columns);
        $joinClause = $join ? $this->join($join) : '';
        [$w, $p] = $this->where($where ?? []);
        $c = $this->clauses($where ?? []);
        return $this->query("SELECT {$cols} FROM {$table}{$joinClause}{$w}{$c}", $p)->fetchAll();
    }

    /**
     * Get a single row.
     */
    public function get(string $table, $columns = '*', array $where = []): ?array
    {
        if (is_array($columns) && !empty($columns)) {
            $firstKey = array_key_first($columns);
            if (is_string($firstKey) && preg_match('/^\[.+\]/', $firstKey)) {
                $join = $columns;
                $columns = $where ?? '*';
                $where = func_num_args() >= 4 ? func_get_arg(3) : [];
            }
        }
        $cols = $this->cols($columns ?? '*');
        $joinClause = isset($join) ? $this->join($join) : '';
        [$w, $p] = $this->where($where ?? []);
        $c = $this->clauses($where ?? []);
        $r = $this->query("SELECT {$cols} FROM {$table}{$joinClause}{$w}{$c} LIMIT 1", $p)->fetch();
        return $r ?: null;
    }

    /**
     * Pick random rows.
     *
     * $app->db->rand('users', '*', 5)
     */
    public function rand(string $table, $columns = '*', int $limit = 1, array $where = []): array
    {
        $cols = $this->cols($columns);
        [$w, $p] = $this->where($where);
        $c = $this->clauses($where);
        $order = in_array($this->type, ['pgsql', 'sqlite']) ? 'RANDOM()' : 'RAND()';
        return $this->query("SELECT {$cols} FROM {$table}{$w}{$c} ORDER BY {$order} LIMIT {$limit}", $p)->fetchAll();
    }

    // ======================== COUNT / HAS / CHECK ========================

    public function has(string $table, $where = []): bool
    {
        return $this->count($table, '*', $where) > 0;
    }

    public function count(string $table, $columns = '*', $where = null): int
    {
        if ($where === null) { $where = $columns; $columns = '*'; }
        [$w, $p] = $this->where($where);
        return (int)$this->query("SELECT COUNT({$this->cols($columns)}) FROM {$table}{$w}", $p)->fetchColumn();
    }

    // ======================== AGGREGATES ========================

    public function max(string $table, string $col, array $where = []): mixed
    {
        [$w, $p] = $this->where($where);
        return $this->query("SELECT MAX({$col}) FROM {$table}{$w}", $p)->fetchColumn();
    }

    public function min(string $table, string $col, array $where = []): mixed
    {
        [$w, $p] = $this->where($where);
        return $this->query("SELECT MIN({$col}) FROM {$table}{$w}", $p)->fetchColumn();
    }

    public function avg(string $table, string $col, array $where = []): mixed
    {
        [$w, $p] = $this->where($where);
        return $this->query("SELECT AVG({$col}) FROM {$table}{$w}", $p)->fetchColumn();
    }

    public function sum(string $table, string $col, array $where = []): mixed
    {
        [$w, $p] = $this->where($where);
        return $this->query("SELECT SUM({$col}) FROM {$table}{$w}", $p)->fetchColumn();
    }

    // ======================== INSERT ========================

    public function insert(string $table, array $data): string|int
    {
        $cols = implode(', ', array_keys($data));
        $ph = implode(', ', array_fill(0, count($data), '?'));
        $this->exec("INSERT INTO {$table} ({$cols}) VALUES ({$ph})", array_values($data));
        return $this->id();
    }

    public function insert_batch(string $table, array $rows): array
    {
        if (empty($rows)) return [];
        $first = reset($rows);
        $cols = implode(', ', array_keys($first));
        $ph = '(' . implode(', ', array_fill(0, count($first), '?')) . ')';
        $all = implode(', ', array_fill(0, count($rows), $ph));
        $params = [];
        foreach ($rows as $r) $params = array_merge($params, array_values($r));
        $this->exec("INSERT INTO {$table} ({$cols}) VALUES {$all}", $params);
        $last = $this->id();
        $ids = [];
        for ($i = 0; $i < count($rows); $i++) $ids[] = $last + $i;
        return $ids;
    }

    /**
     * INSERT OR REPLACE (Medoo replace).
     */
    public function replace(string $table, array $data, ?array $unique = null): int
    {
        if ($unique) {
            $existing = $this->get($table, '*', $unique);
            if ($existing) return $this->update($table, $data, $unique);
        }
        $this->insert($table, $data);
        return $this->exec("SELECT 1"); // dummy, just return row count
    }

    // ======================== UPDATE ========================

    public function update(string $table, array $data, array $where = []): int
    {
        if (empty($where)) return 0;
        $sets = [];
        $params = [];
        foreach ($data as $col => $val) {
            if ($val instanceof \stdClass && isset($val->value)) {
                $sets[] = "{$col} = {$val->value}";
            } else {
                $sets[] = "{$col} = ?";
                $params[] = $val;
            }
        }
        [$w, $wp] = $this->where($where);
        return $this->exec("UPDATE {$table} SET " . implode(', ', $sets) . "{$w}", array_merge($params, $wp));
    }

    // ======================== DELETE ========================

    public function delete(string $table, array $where = []): int
    {
        if (empty($where)) return 0;
        [$w, $p] = $this->where($where);
        return $this->exec("DELETE FROM {$table}{$w}", $p);
    }

    // ======================== PAGINATION ========================

    public function pages(string $table, int $per_page = 10, int $page = 1, array $where = []): array
    {
        $total = $this->count($table, '*', $where);
        $pages = (int)ceil($total / max($per_page, 1));
        $page = max(1, min($page, $pages ?: 1));
        $offset = ($page - 1) * $per_page;
        $where['LIMIT'] = [$offset, $per_page];
        $rows = $this->select($table, '*', $where);
        return ['rows' => $rows, 'total' => $total, 'pages' => $pages, 'page' => $page, 'per_page' => $per_page];
    }

    // ======================== TRANSACTIONS ========================

    public function begin(): void { $this->pdo->beginTransaction(); }
    public function commit(): void { $this->pdo->commit(); }
    public function rollback(): void { $this->pdo->rollBack(); }

    public function transaction(callable $fn): mixed
    {
        $this->begin();
        try { $r = $fn($this); $this->commit(); return $r; }
        catch (\Throwable $e) { $this->rollback(); throw $e; }
    }

    public function action(callable $fn): mixed
    {
        return $fn($this);
    }

    // ======================== UTILITY ========================

    public function id(): string|int
    {
        $id = $this->pdo->lastInsertId();
        return is_numeric($id) ? (int)$id : $id;
    }

    public function pdo(): \PDO { return $this->pdo; }

    public function type(): string { return $this->type; }

    public function quote(string $str): string { return $this->pdo->quote($str); }

    public function error(): ?array
    {
        $e = $this->pdo->errorInfo();
        return $e[0] !== '00000' ? ['code' => $e[0], 'info' => $e[2]] : $this->lastError;
    }

    public function info(): array
    {
        return [
            'server'      => $this->pdo->getAttribute(\PDO::ATTR_SERVER_VERSION),
            'driver'      => $this->type,
            'client'      => $this->pdo->getAttribute(\PDO::ATTR_CLIENT_VERSION),
            'connection'  => $this->pdo->getAttribute(\PDO::ATTR_CONNECTION_STATUS),
        ];
    }

    public function log(bool $on = true): self
    {
        $this->logging = $on;
        if (!$on) $this->log = [];
        return $this;
    }

    public function log_get(): array { return $this->log; }

    public function debug(): self
    {
        $this->logging = true;
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return $this;
    }

    // ======================== QUERY BUILDERS ========================

    private function cols($columns): string
    {
        if ($columns === '*' || $columns === null) return '*';
        if (is_string($columns)) return $columns;
        if (is_array($columns)) {
            $parts = [];
            foreach ($columns as $key => $val) {
                if (is_int($key)) {
                    $parts[] = $val;
                } else {
                    $parts[] = "{$val} AS {$key}"; // alias: ['alias' => 'column']
                }
            }
            return implode(', ', $parts);
        }
        return '*';
    }

    /**
     * Build JOIN clause.
     *
     * ['[>]profiles' => ['user_id' => 'id']]  → LEFT JOIN
     * ['[<]logs' => ['id' => 'user_id']]      → RIGHT JOIN
     * ['[<>]meta' => ['user_id' => 'id']]     → FULL JOIN
     * ['[><]tags' => ['user_id' => 'id']]     → INNER JOIN
     * Multiple: ['[>]a' => [...], '[>]b' => [...]]
     */
    private function join(array $joins): string
    {
        $sql = '';
        foreach ($joins as $table => $on) {
            $type = 'LEFT';
            $t = $table;
            if (preg_match('/^\[(>|>=|<|<=|!|<>|><|><\])\](\w+)$/', $table, $m)) {
                $type = match ($m[1]) {
                    '>'  => 'LEFT',   '<'  => 'RIGHT',
                    '<>' => 'FULL',   '><' => 'INNER',
                    default => 'LEFT',
                };
                $t = $m[2];
            } elseif (preg_match('/^\[(>)\](\w+)$/', $table, $m)) {
                $t = $m[2];
            }
            $conditions = [];
            foreach ($on as $left => $right) $conditions[] = "{$left} = {$right}";
            $sql .= " {$type} JOIN {$t} ON " . implode(' AND ', $conditions);
        }
        return $sql;
    }

    /**
     * Full Medoo-compatible WHERE clause builder.
     *
     * Operators:
     *   'column' => 'value'            → column = ?
     *   'column[>]' => 5               → column > ?
     *   'column[>=]' => 5              → column >= ?
     *   'column[<]' => 10              → column < ?
     *   'column[<=]' => 10             → column <= ?
     *   'column[!]' => 5               → column != ?
     *   'column[~]' => '%text%'        → column LIKE ?
     *   'column[!~]' => '%text%'       → column NOT LIKE ?
     *   'column[<>]' => [1, 10]        → column BETWEEN ? AND ?
     *   'column[><]' => [1, 10]        → column NOT BETWEEN ? AND ?
     *   'column' => [1, 2, 3]          → column IN (?, ?, ?)
     *   'column[!]' => [1, 2]          → column NOT IN (?, ?, ?)
     *   'column' => null               → column IS NULL
     *   'column[!]' => null            → column IS NOT NULL
     *   'OR' => ['a' => 1, 'b' => 2]   → (a = ? OR b = ?)
     *   'AND' => [...]                 → explicit AND group
     *   'MATCH' => ['columns', 'keyword'] → MATCH(cols) AGAINST(?)
     *   'ORDER' => ['id' => 'DESC']    → ORDER BY id DESC
     *   'GROUP' => 'col'               → GROUP BY col
     *   'LIMIT' => 10                  → LIMIT 10
     *   'LIMIT' => [0, 10]             → LIMIT 0, 10
     *   'HAVING' => [...]              → HAVING
     */
    private function where(array $conds): array
    {
        if (empty($conds)) return ['', []];
        $c = []; $p = [];

        foreach ($conds as $k => $v) {
            if (in_array($k, ['LIMIT', 'ORDER', 'GROUP', 'HAVING', 'MATCH'], true)) continue;

            // AND group
            if ($k === 'AND') {
                [$s, $sp] = $this->where($v);
                if ($s) $c[] = '(' . ltrim($s, ' WHERE ') . ')';
                $p = array_merge($p, $sp);
                continue;
            }

            // OR group
            if ($k === 'OR') {
                $or = [];
                foreach ($v as $oc) {
                    if (is_array($oc)) {
                        [$s, $sp] = $this->where($oc);
                        $or[] = '(' . ltrim($s, ' WHERE ') . ')';
                        $p = array_merge($p, $sp);
                    }
                }
                if (!empty($or)) $c[] = '(' . implode(' OR ', $or) . ')';
                continue;
            }

            // Parse operator
            $col = $k; $op = '=';
            if (preg_match('/^(.+)\[([^\]]+)\]$/', $k, $m)) {
                $col = $m[1];
                $op = match ($m[2]) {
                    '>' => '>', '>=' => '>=', '<' => '<', '<=' => '<=',
                    '!' => '!=', '~' => 'LIKE', '!~' => 'NOT LIKE',
                    '<>' => 'BETWEEN', '><' => 'NOT BETWEEN', default => '=',
                };
            }

            // Raw value
            if ($v instanceof \stdClass && isset($v->value)) {
                $c[] = "{$col} {$op} {$v->value}";
                continue;
            }

            // NULL handling
            if ($v === null) {
                if ($op === '!=') $c[] = "{$col} IS NOT NULL";
                else $c[] = "{$col} IS NULL";
                continue;
            }

            // IN / NOT IN (array value, not BETWEEN)
            if (is_array($v) && $op !== 'BETWEEN' && $op !== 'NOT BETWEEN') {
                if (empty($v)) {
                    $c[] = $op === '!=' ? '1=1' : '1=0';
                } else {
                    $ph = implode(', ', array_fill(0, count($v), '?'));
                    $not = ($op === '!=') ? 'NOT' : '';
                    $c[] = "{$col} {$not} IN ({$ph})";
                    $p = array_merge($p, $v);
                }
                continue;
            }

            // BETWEEN / NOT BETWEEN
            if (($op === 'BETWEEN' || $op === 'NOT BETWEEN') && is_array($v) && count($v) === 2) {
                $c[] = "{$col} {$op} ? AND ?";
                $p[] = $v[0]; $p[] = $v[1];
                continue;
            }

            // LIKE / NOT LIKE
            if ($op === 'LIKE' || $op === 'NOT LIKE') {
                $c[] = "{$col} {$op} ?";
                $p[] = $v;
                continue;
            }

            // Standard comparison
            $c[] = "{$col} {$op} ?";
            $p[] = $v;
        }

        return [empty($c) ? '' : ' WHERE ' . implode(' AND ', $c), $p];
    }

    /**
     * Build ORDER, GROUP, HAVING, LIMIT, MATCH clauses.
     */
    private function clauses(array $conds): string
    {
        $sql = '';

        // MATCH (full-text)
        if (isset($conds['MATCH'])) {
            $match = $conds['MATCH'];
            if (is_array($match) && count($match) >= 2) {
                $columns = is_array($match[0]) ? implode(', ', $match[0]) : $match[0];
                $keywords = $match[1];
                $mode = $match[2] ?? 'IN BOOLEAN MODE';
                $sql .= " WHERE MATCH({$columns}) AGAINST(" . $this->quote($keywords) . " {$mode})";
            }
        }

        // GROUP BY
        if (isset($conds['GROUP'])) {
            $g = is_array($conds['GROUP']) ? implode(', ', $conds['GROUP']) : $conds['GROUP'];
            $sql .= " GROUP BY {$g}";
        }

        // ORDER BY
        if (isset($conds['ORDER'])) {
            $o = $conds['ORDER'];
            if (is_array($o)) {
                $parts = [];
                foreach ($o as $col => $dir) {
                    if (is_int($col)) {
                        $parts[] = $dir;
                    } else {
                        $parts[] = "{$col} " . strtoupper((string)$dir);
                    }
                }
                $sql .= " ORDER BY " . implode(', ', $parts);
            } else {
                $sql .= " ORDER BY {$o}";
            }
        }

        // LIMIT
        if (isset($conds['LIMIT'])) {
            $l = $conds['LIMIT'];
            $sql .= is_array($l) ? " LIMIT " . (int)$l[0] . ", " . (int)$l[1] : " LIMIT " . (int)$l;
        }

        // HAVING
        if (isset($conds['HAVING'])) {
            $sql .= " HAVING " . $conds['HAVING'];
        }

        return $sql;
    }
}

// =============================================================================

/**
 * Trindade Mail
 *
 * Fluent mailer. SMTP or PHP mail() fallback. No external dependency.
 *
 * $app->mail
 *   ->to('john@example.com')
 *   ->cc('jane@example.com')
 *   ->subject('Hello')
 *   ->html('<h1>Hello</h1>')
 *   ->send();
 */
class Mail
{
    private array $to = [];
    private array $cc = [];
    private array $bcc = [];
    private string $from_email = '';
    private string $from_name = '';
    private string $subject = '';
    private string $html = '';
    private string $text = '';
    private array $attachments = [];
    private array $headers = [];
    private array $config;
    private array $errors = [];

    public function __construct(array $config = [])
    {
        $this->config = $config;
        if (!empty($config['from'])) $this->from_email = $config['from'];
        if (!empty($config['name'])) $this->from_name = $config['name'];
    }

    public function to(string|array $email, string $name = ''): self
    {
        if (is_array($email)) foreach ($email as $e) $this->to[] = $e;
        else $this->to[] = $name ? "{$name} <{$email}>" : $email;
        return $this;
    }

    public function cc(string|array $email, string $name = ''): self
    {
        if (is_array($email)) foreach ($email as $e) $this->cc[] = $e;
        else $this->cc[] = $name ? "{$name} <{$email}>" : $email;
        return $this;
    }

    public function bcc(string|array $email, string $name = ''): self
    {
        if (is_array($email)) foreach ($email as $e) $this->bcc[] = $e;
        else $this->bcc[] = $name ? "{$name} <{$email}>" : $email;
        return $this;
    }

    public function from(string $email, string $name = ''): self
    {
        $this->from_email = $email;
        $this->from_name = $name;
        return $this;
    }

    public function subject(string $s): self { $this->subject = $s; return $this; }
    public function html(string $h): self { $this->html = $h; return $this; }
    public function text(string $t): self { $this->text = $t; return $this; }
    public function message(string $body): self { $this->html = $body; return $this; }
    public function header(string $k, string $v): self { $this->headers[$k] = $v; return $this; }
    public function errors(): array { return $this->errors; }

    public function attach(string $path, ?string $name = null): self
    {
        if (file_exists($path)) {
            $this->attachments[] = ['path' => $path, 'name' => $name ?? basename($path)];
        }
        return $this;
    }

    public function send(): bool
    {
        $this->errors = [];
        if (empty($this->to)) { $this->errors[] = 'No recipients'; return false; }
        if (empty($this->from_email)) { $this->errors[] = 'No sender'; return false; }

        if (!empty($this->config['host'])) return $this->send_smtp();
        return $this->send_php();
    }

    private function send_php(): bool
    {
        try {
            $to = implode(', ', $this->to);
            $headers = ['MIME-Version: 1.0'];
            $headers[] = $this->from_name
                ? "From: {$this->from_name} <{$this->from_email}>"
                : "From: {$this->from_email}";
            if (!empty($this->cc)) $headers[] = 'Cc: ' . implode(', ', $this->cc);
            if (!empty($this->bcc)) $headers[] = 'Bcc: ' . implode(', ', $this->bcc);
            foreach ($this->headers as $k => $v) $headers[] = "{$k}: {$v}";

            if (!empty($this->attachments)) {
                $boundary = 'Trindade-boundary';
                $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
                $message = $this->build_mixed($boundary);
            } elseif (!empty($this->html) && !empty($this->text)) {
                $b = md5(uniqid());
                $headers[] = 'Content-Type: multipart/alternative; boundary="' . $b . '"';
                $message = "--{$b}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n{$this->text}\r\n\r\n"
                         . "--{$b}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n{$this->html}\r\n\r\n"
                         . "--{$b}--";
            } elseif (!empty($this->html)) {
                $headers[] = 'Content-Type: text/html; charset=UTF-8';
                $message = $this->html;
            } else {
                $headers[] = 'Content-Type: text/plain; charset=UTF-8';
                $message = $this->text;
            }

            $ok = @mail($to, $this->subject, $message ?? '', implode("\r\n", $headers));
            if (!$ok) $this->errors[] = 'mail() failed';
            $this->reset();
            return $ok;
        } catch (\Throwable $e) { $this->errors[] = $e->getMessage(); $this->reset(); return false; }
    }

    private function send_smtp(): bool
    {
        try {
            $host = $this->config['host'];
            $port = (int)($this->config['port'] ?? 587);
            $secure = $this->config['secure'] ?? 'tls';
            $user = $this->config['username'] ?? '';
            $pass = $this->config['password'] ?? '';

            $s = @fsockopen(($secure === 'ssl' ? 'ssl://' : '') . $host, $port, $en, $es, 10);
            if (!$s) { $this->errors[] = "SMTP: {$es} ({$en})"; $this->reset(); return false; }

            $this->smtp_read($s);
            $this->smtp_cmd($s, "EHLO " . gethostname());

            if ($secure === 'tls') {
                $this->smtp_cmd($s, "STARTTLS");
                stream_socket_enable_crypto($s, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $this->smtp_cmd($s, "EHLO " . gethostname());
            }

            if (!empty($user)) {
                $this->smtp_cmd($s, "AUTH LOGIN");
                $this->smtp_cmd($s, base64_encode($user));
                $this->smtp_cmd($s, base64_encode($pass));
            }

            $this->smtp_cmd($s, "MAIL FROM:<{$this->from_email}>");
            foreach (array_merge($this->to, $this->cc, $this->bcc) as $r) {
                $addr = $this->extract($r);
                $this->smtp_cmd($s, "RCPT TO:<{$addr}>");
            }

            $this->smtp_cmd($s, "DATA");
            fwrite($s, $this->build_mime() . "\r\n.\r\n");
            $this->smtp_read($s);
            $this->smtp_cmd($s, "QUIT");
            fclose($s);
            $this->reset();
            return true;
        } catch (\Throwable $e) { $this->errors[] = $e->getMessage(); $this->reset(); return false; }
    }

    private function build_mixed(string $boundary): string
    {
        $msg = "This is a multi-part message in MIME format.\r\n--{$boundary}\r\n";
        $alt = md5(uniqid()) . '-alt';
        $ht = !empty($this->html); $tt = !empty($this->text);

        if ($tt && $ht) {
            $msg .= "Content-Type: multipart/alternative; boundary=\"{$alt}\"\r\n\r\n"
                  . "--{$alt}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n{$this->text}\r\n\r\n"
                  . "--{$alt}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n{$this->html}\r\n\r\n"
                  . "--{$alt}--\r\n";
        } elseif ($ht) {
            $msg .= "Content-Type: text/html; charset=UTF-8\r\n\r\n{$this->html}\r\n";
        } elseif ($tt) {
            $msg .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n{$this->text}\r\n";
        }

        foreach ($this->attachments as $a) {
            $c = chunk_split(base64_encode(file_get_contents($a['path'])));
            $msg .= "--{$boundary}\r\n"
                  . "Content-Type: application/octet-stream; name=\"{$a['name']}\"\r\n"
                  . "Content-Disposition: attachment; filename=\"{$a['name']}\"\r\n"
                  . "Content-Transfer-Encoding: base64\r\n\r\n{$c}\r\n";
        }
        return $msg . "--{$boundary}--";
    }

    private function build_mime(): string
    {
        $from = $this->from_name ? "{$this->from_name} <{$this->from_email}>" : $this->from_email;
        $msg = "From: {$from}\r\n";
        if (!empty($this->to)) $msg .= "To: " . implode(', ', $this->to) . "\r\n";
        if (!empty($this->cc)) $msg .= "Cc: " . implode(', ', $this->cc) . "\r\n";
        if (!empty($this->bcc)) $msg .= "Bcc: " . implode(', ', $this->bcc) . "\r\n";
        $msg .= "Subject: {$this->subject}\r\nMIME-Version: 1.0\r\n";
        foreach ($this->headers as $k => $v) $msg .= "{$k}: {$v}\r\n";

        if (!empty($this->attachments)) {
            $b = 'Trindade-boundary';
            $msg .= "Content-Type: multipart/mixed; boundary=\"{$b}\"\r\n\r\n" . $this->build_mixed($b);
        } elseif (!empty($this->html) && !empty($this->text)) {
            $b = md5(uniqid());
            $msg .= "Content-Type: multipart/alternative; boundary=\"{$b}\"\r\n\r\n"
                  . "--{$b}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n{$this->text}\r\n\r\n"
                  . "--{$b}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n{$this->html}\r\n\r\n"
                  . "--{$b}--";
        } elseif (!empty($this->html)) {
            $msg .= "Content-Type: text/html; charset=UTF-8\r\n\r\n{$this->html}";
        } else {
            $msg .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n{$this->text}";
        }
        return $msg;
    }

    private function smtp_cmd($s, string $cmd): void
    {
        fwrite($s, $cmd . "\r\n");
        $r = $this->smtp_read($s);
        $code = (int)substr($r, 0, 3);
        if ($code >= 400) throw new \Exception("SMTP {$code}: {$r}");
    }

    private function smtp_read($s): string
    {
        $r = '';
        while ($l = fgets($s, 512)) { $r .= $l; if (isset($l[3]) && $l[3] === ' ') break; }
        return trim($r);
    }

    private function extract(string $s): string
    {
        if (preg_match('/<(.+?)>/', $s, $m)) return trim($m[1]);
        return trim($s);
    }

    private function reset(): void
    {
        $this->to = []; $this->cc = []; $this->bcc = [];
        $this->subject = ''; $this->html = ''; $this->text = '';
        $this->attachments = []; $this->headers = [];
        if (!empty($this->config['from'])) $this->from_email = $this->config['from'];
        if (!empty($this->config['name'])) $this->from_name = $this->config['name'];
    }
}

// =============================================================================

/**
 * Trindade WebSocket Server
 *
 * Start: php bin/ws start --port=8080
 * Access via $app->ws when loaded as plugin.
 *
 * Requires: cboden/ratchet
 */
class WebSocket implements \Ratchet\MessageComponentInterface
{
    private array $clients = [];
    private \SplObjectStorage $conns;
    private string $host;
    private int $port;
    private array $events = [];
    private array $channels = [];

    public function __construct(array $config = [])
    {
        $this->conns = new \SplObjectStorage();
        $this->host = $config['host'] ?? '0.0.0.0';
        $this->port = (int)($config['port'] ?? 8080);
    }

    public function start(): void
    {
        if (php_sapi_name() !== 'cli') throw new \RuntimeException('CLI only');

        echo "Trindade WS on ws://{$this->host}:{$this->port}\n";

        $server = \Ratchet\Server\IoServer::factory(
            new \Ratchet\Http\HttpServer(new \Ratchet\WebSocket\WsServer($this)),
            $this->port,
            $this->host
        );
        $server->run();
    }

    public function onOpen(\Ratchet\ConnectionInterface $conn): void
    {
        $this->conns->attach($conn);
        $id = spl_object_hash($conn);
        $this->clients[$id] = $conn;
        $this->emit('open', ['id' => $id]);
    }

    public function onMessage(\Ratchet\ConnectionInterface $from, $msg): void
    {
        $id = spl_object_hash($from);
        $data = json_decode($msg, true) ?: ['raw' => $msg];
        $type = $data['type'] ?? 'message';

        if ($type === 'join' && isset($data['channel'])) {
            $this->channels[$data['channel']][$id] = $from;
            $from->send(json_encode(['type' => 'joined', 'channel' => $data['channel']]));
            return;
        }
        if ($type === 'leave' && isset($data['channel'])) {
            unset($this->channels[$data['channel']][$id]);
            $from->send(json_encode(['type' => 'left', 'channel' => $data['channel']]));
            return;
        }

        $this->emit('message', ['id' => $id, 'data' => $data]);
    }

    public function onClose(\Ratchet\ConnectionInterface $conn): void
    {
        $id = spl_object_hash($conn);
        foreach ($this->channels as $ch => $c) unset($this->channels[$ch][$id]);
        unset($this->clients[$id]);
        $this->conns->detach($conn);
        $this->emit('close', ['id' => $id]);
    }

    public function onError(\Ratchet\ConnectionInterface $conn, \Exception $e): void
    {
        $conn->close();
    }

    public function send(string $id, string $type, array $data = []): void
    {
        if (isset($this->clients[$id])) {
            $this->clients[$id]->send(json_encode(array_merge(['type' => $type], $data)));
        }
    }

    public function broadcast(string $type, array $data = []): void
    {
        $payload = json_encode(array_merge(['type' => $type], $data));
        foreach ($this->conns as $client) $client->send($payload);
    }

    public function channel(string $channel, string $type, array $data = []): void
    {
        if (!isset($this->channels[$channel])) return;
        $payload = json_encode(array_merge(['type' => $type, 'channel' => $channel], $data));
        foreach ($this->channels[$channel] as $client) $client->send($payload);
    }

    public function on(string $event, callable $handler): self
    {
        $this->events[$event][] = $handler;
        return $this;
    }

    private function emit(string $event, array $data = []): void
    {
        if (!isset($this->events[$event])) return;
        foreach ($this->events[$event] as $handler) $handler($data);
    }

    public function count(): int { return $this->conns->count(); }
    public function channels(): array { return array_keys($this->channels); }
}

// =============================================================================

/**
 * Trindade Framework Core
 *
 * Minimalist, secure PHP framework. Everything accessible via $app->...
 * Single words, no camelCase, no snake_case.
 *
 * @package     Trindade
 * @author      Daniel Medina <jdanielcmedina@gmail.com>
 * @copyright   2026 Daniel Medina
 * @license     MIT License
 * @version     2.0.0
 */
class Trindade
{
    private array $config;
    private array $paths = [];
    private array $routes = [];
    private string $group = '';
    private string $vhost = '';
    private array $params = [];
    private array $notfound = [];
    private array $middleware = [];
    private array $plugins = [];
    private array $listeners = [];
    private $fallback = null;
    private int $code = 200;

    public $db = null;
    private array $dbs = [];
    private float $started;
    public $mail = null;
    public $ws = null;

    private array $perm = ['folder' => 0755, 'private' => 0600, 'public' => 0644];

    private const CSS = '
        :root{--bg:#09090b;--surface:#18181b;--border:#27272a;--text:#fafafa;--muted:#a1a1aa;--red:#ef4444;--amber:#f59e0b;--blue:#3b82f6}
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:"Inter",-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;-webkit-font-smoothing:antialiased}
        .wrap{width:100%;max-width:720px;padding:2rem}
        .card{background:var(--surface);border:1px solid var(--border);border-radius:16px;overflow:hidden}
        .hdr{display:flex;align-items:flex-start;gap:1.25rem;padding:2rem 2rem 1.5rem}
        .code-badge{display:flex;align-items:center;justify-content:center;width:56px;height:56px;border-radius:14px;font-size:20px;font-weight:700;font-family:"JetBrains Mono","SF Mono",monospace;flex-shrink:0}
        .code-badge.e4{background:#3b82f610;color:var(--blue);border:1px solid #3b82f620}
        .code-badge.e5{background:#ef444410;color:var(--red);border:1px solid #ef444420}
        .hdr-info h1{font-size:1.15rem;font-weight:600;letter-spacing:-.3px;margin-bottom:.25rem}
        .hdr-info p{font-size:.875rem;color:var(--muted);line-height:1.5}
        .trace-section{border-top:1px solid var(--border);padding:1.5rem 2rem}
        .trace-label{font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:.75rem}
        .trace{font-family:"JetBrains Mono","SF Mono",Menlo,monospace;font-size:.75rem;line-height:1.8;color:var(--muted);white-space:pre-wrap;overflow-x:auto}
        .trace .file{color:var(--blue)}
        .trace .line{color:var(--amber)}
        .trace .fn{color:#818cf8}
        .footer{padding:1rem 2rem;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;font-size:.75rem;color:var(--muted)}
        .footer span{display:flex;align-items:center;gap:.35rem}
        .dot{width:6px;height:6px;border-radius:50%;background:#22c55e}
        @keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}
    ';

    private const ERROR_PAGES = [
        400 => 'The request could not be understood.',
        401 => 'You need to authenticate to access this resource.',
        403 => 'You do not have permission to access this resource.',
        404 => 'The page you are looking for does not exist.',
        405 => 'This method is not allowed for this endpoint.',
        408 => 'The request took too long and timed out.',
        409 => 'There is a conflict with the current state of the resource.',
        422 => 'The provided data failed validation.',
        429 => 'You have made too many requests. Please slow down.',
        500 => 'Something went wrong on our end. We are working on it.',
        502 => 'The server received an invalid response from an upstream service.',
        503 => 'The service is temporarily unavailable. Please try again later.',
        504 => 'An upstream service took too long to respond.',
    ];

    public function __construct(array $config = [], ?array $database = null)
    {
        $this->started = microtime(true);
        try {
            $root = rtrim($config['root'] ?? dirname(getcwd()), '/\\') . DIRECTORY_SEPARATOR;
            unset($config['root']);

            $this->paths = [
                'root'    => $root,
                'routes'  => $root . 'routes',
                'views'   => $root . 'views',
                'helpers' => $root . 'helpers',
                'storage' => [
                    'cache'   => $root . 'storage/cache',
                    'logs'    => $root . 'storage/logs',
                    'uploads' => $root . 'storage/uploads',
                    'temp'    => $root . 'storage/temp',
                    'backups' => $root . 'storage/backups',
                    'queue'   => $root . 'storage/queue',
                    'failed'  => $root . 'storage/queue/failed',
                ],
            ];

            foreach ($this->paths['storage'] as $type => $path) {
                if (!is_dir($path)) @mkdir($path, $this->perm['folder'], true);
                if ($type === 'uploads') @chmod($path, $this->perm['public']);
            }

            // Load config.php from project root if exists
            $cfg_file = $root . 'config.php';
            if (file_exists($cfg_file)) {
                $file_config = require $cfg_file;
                if (is_array($file_config)) $config = array_replace_recursive($file_config, $config);
            }

            $this->config = array_replace_recursive([
                'debug'    => false,
                'secure'   => false,
                'errors'   => true,
                'timezone' => 'UTC',
                'upload'   => [
                    'max_size'   => 5242880,
                    'types'      => ['image/jpeg', 'image/png', 'application/pdf'],
                    'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'webp'],
                ],
                'cache'    => ['ttl' => 3600],
                'cors'     => [
                    'on'          => false,
                    'origins'     => [],
                    'methods'     => 'GET, POST, PUT, DELETE, OPTIONS, PATCH',
                    'headers'     => 'Content-Type, Authorization, X-Csrf-Token',
                    'credentials' => false,
                ],
                'security' => ['rate' => false, 'max' => 60, 'window' => 60],
            ], $config);

            if ($database) $this->config['db'] = $database;

            $this->secure();
            date_default_timezone_set($this->config['timezone'] ?? 'UTC');
            $this->init();
            $this->load('helpers');
        } catch (\Throwable $e) {
            $this->debug(
                $this->config['debug'] ? $e->getMessage() : 'Internal server error',
                500,
                $this->config['debug'] ? $e->getTraceAsString() : null
            );
        }
    }

    private function secure(): void
    {
        if (!($this->config['secure'] ?? false)) return;

        $is = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] === 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        if (!$is && !headers_sent()) {
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            exit;
        }
    }

    private function init(): void
    {
        try {
            // Multiple databases: config 'db' or 'databases' array
            if (isset($this->config['databases'])) {
                foreach ($this->config['databases'] as $name => $cfg) {
                    try { $this->dbs[$name] = new Database($cfg); }
                    catch (\PDOException $e) { $this->log("DB error: {$name}", "error"); }
                }
                $this->db = $this->dbs['default'] ?? ($this->dbs['main'] ?? reset($this->dbs));
            } elseif (isset($this->config['db'])) {
                try {
                    $this->db = new Database($this->config['db']);
                    $this->dbs['default'] = $this->db;
                } catch (\PDOException $e) {
                    $this->log("DB error", "error");
                    $this->db = null;
                }
            }

            if (isset($this->config['mail'])) {
                $this->mail = new Mail($this->config['mail']);
            } else {
                $this->mail = new Mail([]); // sempre disponível
            }

            if (!isset($this->config['test'])) {
                $this->load('routes');
                register_shutdown_function([$this, 'run']);
            }

            $this->plugins();
        } catch (\Throwable $e) {
            $this->debug(
                $this->config['debug'] ? $e->getMessage() : 'Internal server error',
                500,
                $this->config['debug'] ? $e->getTraceAsString() : null
            );
        }
    }

    private function plugins(): void
    {
        $path = $this->paths['root'] . 'plugins' . DIRECTORY_SEPARATOR;
        if (!is_dir($path)) return;

        foreach (new \DirectoryIterator($path) as $file) {
            if ($file->isDot() || $file->isDir() || $file->getExtension() !== 'php') continue;
            $class = 'Trindade\\Plugins\\' . $file->getBasename('.php');
            if (class_exists($class)) {
                $name = strtolower($file->getBasename('.php'));
                $this->plugins[$name] = new $class($this);
                $this->log("Plugin: {$name}", "debug");
            }
        }
    }

    // ======================== ROUTING ========================

    public function on(string $route, callable $handler): self
    {
        $parts = explode(' ', $route, 2);
        $methods = explode('|', $parts[0]);
        $path = $parts[1] ?? '';

        $full = $path;
        if ($this->group) $full = rtrim($this->group, '/') . '/' . ltrim($path, '/');
        $full = $full === '/' ? '/' : rtrim($full, '/');

        $fn = $handler instanceof \Closure
            ? \Closure::bind($handler, $this, static::class)
            : $handler;
        foreach ($methods as $m) {
            $this->routes[$m][$full] = ['fn' => $fn, 'vhost' => $this->vhost];
        }
        return $this;
    }

    public function any(callable $handler): self { $this->fallback = $handler; return $this; }

    /**
     * List registered routes (for CLI inspection).
     */
    public function routes(): array { return $this->routes; }

    /**
     * Add middleware to the pipeline.
     *
     * $app->use(function ($next) use ($app) {
     *     if (!$app->csrf_check()) return $app->error('CSRF', 403);
     *     return $next();
     * });
     */
    public function use(callable $handler): self
    {
        $this->middleware[] = $handler;
        return $this;
    }

    // ======================== EVENTS ========================

    /**
     * Register an event listener.
     *
     * $app->listen('user.created', function ($data) use ($app) {
     *     $app->mail->to($data['email'])->subject('Welcome')->send();
     * });
     */
    public function listen(string $event, callable $handler): self
    {
        $this->listeners[$event][] = $handler;
        return $this;
    }

    /**
     * Emit an event — triggers all registered listeners and broadcasts to WebSocket.
     *
     * $app->emit('user.created', ['email' => 'x@x.com', 'id' => 42]);
     */
    public function emit(string $event, array $data = []): self
    {
        // Broadcast to WebSocket clients (if ws plugin loaded)
        if (isset($this->plugins['websocket'])) {
            try { $this->plugins['websocket']->broadcast($event, $data); } catch (\Throwable $e) {}
        }

        // Trigger PHP listeners
        if (!isset($this->listeners[$event])) return $this;
        foreach ($this->listeners[$event] as $handler) {
            try { $handler($data); }
            catch (\Throwable $e) { $this->log("Event {$event}: " . $e->getMessage(), "error"); }
        }
        return $this;
    }

    // ======================== SSE (Server-Sent Events) ========================

    /**
     * Start a Server-Sent Events stream. Keeps the connection open and sends events.
     *
     * $app->sse(function ($send) use ($app) {
     *     while (true) {
     *         $send('message', $app->db->select('events', '*', ['id[>]' => $lastId]));
     *         sleep(1);
     *     }
     * });
     */
    public function sse(callable $handler, int $retry = 3000): void
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        if (ob_get_level()) ob_end_flush();

        $send = function (string $event, $data) use ($retry) {
            echo "event: {$event}\n";
            echo 'data: ' . (is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE)) . "\n";
            echo "retry: {$retry}\n\n";
            if (ob_get_level()) ob_flush();
            flush();
        };

        // Send initial connection event
        $send('connected', ['time' => date('c')]);

        try {
            $handler($send);
        } catch (\Throwable $e) {
            $send('error', $e->getMessage());
        }
    }

    /**
     * Subscribe to events via SSE (simpler API — auto-subscribes to Trindade events).
     *
     * $app->sse_events();  // streams all emitted events
     */
    public function sse_events(array $events = ['*']): void
    {
        $this->sse(function ($send) use ($events) {
            $lastId = 0;
            $eventLog = $this->storage('cache') . '/_sse_events.log';

            while (true) {
                if (file_exists($eventLog)) {
                    $lines = file($eventLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    if ($lines) {
                        foreach ($lines as $line) {
                            $data = json_decode($line, true);
                            if ($data && ($events === ['*'] || in_array($data['event'] ?? '', $events))) {
                                $send($data['event'] ?? 'message', $data['data'] ?? $data);
                            }
                        }
                        file_put_contents($eventLog, '');
                    }
                }
                usleep(500000); // 0.5s
            }
        });
    }

    // ======================== SCHEDULER ========================

    /**
     * Schedule a task. Supports cron expressions and named intervals.
     *
     * $app->schedule('daily', fn() => $app->cleanup());
     * $app->schedule('0 3 * * *', fn() => $app->backup());
     * $app->schedule('everyMinute', fn() => $app->cache('stats', computeStats()));
     *
     * Named: everyMinute, everyFiveMinutes, hourly, daily, weekly, monthly
     */
    public function schedule(string $expression, callable $task): self
    {
        $cron = match ($expression) {
            'everyMinute'      => '* * * * *',
            'everyFiveMinutes' => '*/5 * * * *',
            'everyTenMinutes'  => '*/10 * * * *',
            'everyThirtyMinutes' => '*/30 * * * *',
            'hourly'           => '0 * * * *',
            'daily'            => '0 3 * * *',
            'weekly'           => '0 3 * * 0',
            'monthly'          => '0 3 1 * *',
            'yearly'           => '0 3 1 1 *',
            default            => $expression,
        };

        $file = $this->storage('cache') . '/schedule/' . md5($cron . spl_object_id($task));
        if (!is_dir(dirname($file))) mkdir(dirname($file), 0755, true);

        file_put_contents($file, serialize(['cron' => $cron, 'handler' => $task]));

        return $this;
    }

    /**
     * Run due scheduled tasks. Called by bin/worker.
     */
    public function schedule_run(): array
    {
        $run = [];
        $dir = $this->storage('cache') . '/schedule/';
        if (!is_dir($dir)) return $run;

        $now = new \DateTime();
        foreach (glob($dir . '*') as $file) {
            $task = unserialize(file_get_contents($file));
            if (!$task) continue;

            if ($this->cron_matches($task['cron'], $now)) {
                try {
                    $handler = $task['handler'];
                    $handler();
                    $run[] = "Ran: {$task['cron']}";
                    $this->log("Scheduler: ran {$task['cron']}", "info");
                } catch (\Throwable $e) {
                    $run[] = "Error: {$task['cron']} — {$e->getMessage()}";
                    $this->log("Scheduler error: {$task['cron']} — {$e->getMessage()}", "error");
                }
            }
        }

        return $run;
    }

    private function cron_matches(string $cron, \DateTime $now): bool
    {
        $parts = explode(' ', $cron);
        if (count($parts) !== 5) return false;

        $map = [$now->format('i'), $now->format('H'), $now->format('d'), $now->format('m'), $now->format('w')];

        foreach ($parts as $i => $p) {
            if ($p === '*') continue;
            foreach (explode(',', $p) as $v) {
                if (strpos($v, '/') !== false) {
                    [$step, $every] = explode('/', $v);
                    if ($every === '*') $every = '0';
                    if ((int)$map[$i] % (int)$every === 0) continue 2;
                } elseif ((int)$map[$i] === (int)$v) {
                    continue 2;
                }
            }
            return false;
        }
        return true;
    }

    // ======================== QUEUE ========================

    /**
     * Push a job to the queue.
     *
     * $app->queue('send-email', ['to' => 'x@x.com']);
     * $app->queue('cleanup', $data)->delay(300);
     */
    public function queue(string $job, array $payload = [], int $delay = 0): self
    {
        $dir = $this->storage('queue');
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $id = bin2hex(random_bytes(8));
        $file = $dir . '/' . ($delay ? 'delayed_' : '') . $id . '.job';

        file_put_contents($file, json_encode([
            'id'        => $id,
            'job'       => $job,
            'payload'   => $payload,
            'delay'     => $delay,
            'available' => time() + $delay,
            'attempts'  => 0,
            'created'   => date('c'),
        ]));

        $this->emit('queue.pushed', ['job' => $job, 'id' => $id]);
        $this->log("Queue: {$job} pushed", "debug");

        return $this;
    }

    /**
     * Process queued jobs. Called by bin/worker.
     */
    public function queue_work(int $timeout = 60, int $max = 0): array
    {
        $dir = $this->storage('queue');
        if (!is_dir($dir)) return [];

        $processed = [];
        $deadline = time() + $timeout;
        $count = 0;

        while (time() < $deadline) {
            $job = $this->queue_next();
            if (!$job) break;

            try {
                $this->emit('queue.processing', ['job' => $job['job'], 'id' => $job['id']]);

                if ($job['job'] === 'callback' && isset($job['payload']['callback'])) {
                    $fn = $job['payload']['callback'];
                    if (is_string($fn) && function_exists($fn)) $fn($job['payload']);
                    elseif ($fn instanceof \Closure) $fn($job['payload']);
                } else {
                    $this->emit('queue.job.' . $job['job'], $job['payload']);
                }

                unlink($dir . '/' . basename($job['_file']));
                $processed[] = "OK: {$job['job']} ({$job['id']})";
                $this->log("Queue: {$job['job']} completed", "debug");
            } catch (\Throwable $e) {
                $job['attempts']++;
                $job['_error'] = $e->getMessage();
                $this->log("Queue error: {$job['job']} — {$e->getMessage()}", "error");

                if ($job['attempts'] >= 3) {
                    $failDir = $this->storage('failed');
                    if (!is_dir($failDir)) mkdir($failDir, 0755, true);
                    file_put_contents($failDir . '/' . $job['id'] . '.failed', json_encode($job));
                    unlink($dir . '/' . basename($job['_file']));
                    $processed[] = "FAILED: {$job['job']} ({$job['id']})";
                } else {
                    file_put_contents($dir . '/' . basename($job['_file']), json_encode($job));
                }
            }

            $count++;
            if ($max > 0 && $count >= $max) break;
            usleep(100000);
        }

        return $processed;
    }

    /**
     * Get the next available job from the queue.
     */
    private function queue_next(): ?array
    {
        $dir = $this->storage('queue');
        if (!is_dir($dir)) return null;

        $files = glob($dir . '/*.job');
        if (empty($files)) return null;

        usort($files, fn($a, $b) => filemtime($a) <=> filemtime($b));

        foreach ($files as $file) {
            $job = json_decode(file_get_contents($file), true);
            if (!$job) continue;

            if (($job['available'] ?? 0) > time()) continue;
            $job['_file'] = $file;
            return $job;
        }

        return null;
    }

    /**
     * Get queue stats.
     */
    public function queue_status(): array
    {
        $dir = $this->storage('queue');
        $fail = $this->storage('failed');

        $pending = count(glob($dir . '/*.job'));
        $pending += count(glob($dir . '/delayed_*.job'));
        $failed = is_dir($fail) ? count(glob($fail . '/*.failed')) : 0;

        return ['pending' => $pending, 'failed' => $failed];
    }

    /**
     * Retry all failed jobs.
     */
    public function queue_retry(): int
    {
        $fail = $this->storage('failed');
        $dir = $this->storage('queue');
        if (!is_dir($fail)) return 0;

        $count = 0;
        foreach (glob($fail . '/*.failed') as $f) {
            $job = json_decode(file_get_contents($f), true);
            if ($job) {
                $job['attempts'] = 0;
                $job['available'] = time();
                file_put_contents($dir . '/' . $job['id'] . '.job', json_encode($job));
                unlink($f);
                $count++;
            }
        }
        return $count;
    }

    public function group(string $prefix, callable $handler): self
    {
        $prev = $this->group;
        if ($prefix !== '/') $prefix = rtrim($prefix, '/');
        $this->group = $prev ? rtrim($prev, '/') . '/' . ltrim($prefix, '/') : $prefix;
        $handler($this);
        $this->group = $prev;
        return $this;
    }

    public function vhost(string $host, callable $handler): self
    {
        $prev = $this->vhost; $this->vhost = $host;
        $handler($this);
        $this->vhost = $prev;
        return $this;
    }

    public function notfound(callable $handler): self
    {
        $this->notfound[$this->group ?: '/'] = $handler;
        return $this;
    }

    public function run(): void
    {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $uri = $uri === '/' ? '/' : rtrim($uri, '/');
            $host = $_SERVER['HTTP_HOST'] ?? '';

            if ($method === 'OPTIONS' && ($this->config['cors']['on'] ?? false)) {
                $this->cors(); http_response_code(204); exit;
            }

            $this->send_headers();

            // Route matching (the final step in middleware chain)
            $route_handler = function () use ($method, $uri, $host) {
                if (isset($this->routes[$method])) {
                    foreach ($this->routes[$method] as $pattern => $route) {
                        if (!empty($route['vhost']) && $route['vhost'] !== $host) continue;

                        $regex = preg_replace('/:[a-zA-Z]+/', '([^/]+)', $pattern);
                        $regex = '/^' . str_replace('/', '\/', $regex) . '$/';

                        if (preg_match($regex, $uri, $m)) {
                            preg_match_all('/:([a-zA-Z]+)/', $pattern, $names);
                            array_shift($m);
                            $this->params = array_combine($names[1] ?? [], $m);

                            if ($this->config['security']['rate'] ?? false) $this->rate($uri);

                            return $route['fn']($this);
                        }
                    }
                }
                return $this->notfound_run($uri, true);
            };

            // Build middleware chain
            $chain = $route_handler;
            foreach (array_reverse($this->middleware) as $mw) {
                $next = $chain;
                $chain = function () use ($mw, $next) {
                    return $mw($next);
                };
            }

            $r = $chain();

            if ($r !== null) {
                if (is_array($r)) {
                    if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
                    echo json_encode($r, JSON_UNESCAPED_UNICODE);
                } else echo $r;
            }
        } catch (\Throwable $e) {
            $this->debug(
                $this->config['debug'] ? $e->getMessage() : 'Internal server error',
                500,
                $this->config['debug'] ? $e->getTraceAsString() : null
            );
        }
    }

    private function notfound_run(string $uri, bool $return = false)
    {
        $handler = null; $best = 0;
        foreach ($this->notfound as $prefix => $fn) {
            if (strpos($uri, $prefix) === 0 && strlen($prefix) > $best) {
                $best = strlen($prefix); $handler = $fn;
            }
        }
        if ($handler) {
            $r = $handler($this);
            if ($return) return $r;
            if ($r !== null) {
                if (is_array($r)) {
                    if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
                    echo json_encode($r, JSON_UNESCAPED_UNICODE);
                } else echo $r;
                return;
            }
        }
        if ($return) { $this->debug('Not Found', 404); }
        $this->debug('Not Found', 404);
    }

    // ======================== RESPONSE ========================

    public function response($data, string $type = 'json', ?int $code = null)
    {
        if ($code !== null) $this->code = $code;
        http_response_code($this->code);

        match ($type) {
            'json' => $this->header('Content-Type', 'application/json; charset=utf-8'),
            'text' => $this->header('Content-Type', 'text/plain; charset=utf-8'),
            'html' => $this->header('Content-Type', 'text/html; charset=utf-8'),
            'xml'  => $this->header('Content-Type', 'application/xml; charset=utf-8'),
            default => null,
        };

        echo is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE);
        $this->code = 200;
        return null;
    }

    public function error(string|array $msg = 'Not found', int $code = 404)
    {
        $payload = is_array($msg) ? array_merge(['error' => true], $msg) : ['error' => true, 'message' => $msg];
        return $this->response($payload, 'json', $code);
    }

    public function success($data = null, string $msg = 'Success')
    {
        return $this->response(['error' => false, 'message' => $msg, 'data' => $data], 'json');
    }

    public function raw($content, string $type = 'text/html')
    {
        if (!headers_sent()) header('Content-Type: ' . $type . '; charset=utf-8');
        echo $content;
        return null;
    }

    public function redirect(string $url): void { header('Location: ' . $url); exit; }
    public function status(int $code): self { $this->code = $code; return $this; }

    // ======================== VIEWS ========================

    private function view_path(string $file): string
    {
        $resolved = realpath($file);
        $views = realpath($this->paths['views']);
        if ($resolved && $views && strpos($resolved, $views) === 0) return $resolved;
        if (strpos(str_replace('\\', '/', $file), '../') !== false) throw new \Exception("Path traversal blocked");
        return $file;
    }

    public function view(string $file, array $data = [], ?int $code = null)
    {
        try {
            if ($code !== null) $this->code = $code;
            http_response_code($this->code);
            if (!pathinfo($file, PATHINFO_EXTENSION)) $file .= '.php';

            $path = $file;
            if (!file_exists($path)) $path = $this->paths['views'] . '/' . ltrim($file, '/');
            $path = $this->view_path($path);

            if (!file_exists($path)) throw new \Exception("View not found: {$file}");

            extract($data);
            ob_start();
            try { include $path; echo ob_get_clean(); $this->code = 200; }
            catch (\Throwable $e) { ob_end_clean(); throw $e; }
            return null;
        } catch (\Throwable $e) {
            $this->debug(
                $this->config['debug'] ? $e->getMessage() : 'Error rendering view',
                500,
                $this->config['debug'] ? $e->getTraceAsString() : null
            );
            return null;
        }
    }

    public function partial(string $name, array $data = []): void
    {
        if (!pathinfo($name, PATHINFO_EXTENSION)) $name .= '.php';
        $path = $this->view_path($this->paths['views'] . '/partials/' . ltrim($name, '/'));
        if (!file_exists($path)) throw new \Exception("Partial not found: {$name}");
        extract($data);
        include $path;
    }

    public function layout(string $view, string $layout = 'default', array $data = [], ?int $code = null)
    {
        if ($code !== null) $this->code = $code;
        ob_start();
        $this->view($view, $data);
        $data['content'] = ob_get_clean();
        return $this->view('layouts/' . $layout, $data, $code);
    }

    // ======================== REQUEST ========================

    public function param(string $name) { return $this->params[$name] ?? null; }
    public function params(): array { return $this->params; }

    public function body(?string $key = null)
    {
        static $cache = null;
        if ($cache === null) {
            $input = file_get_contents('php://input');
            $type = $this->header('Content-Type');
            if (strpos($type, 'application/json') !== false) $cache = json_decode($input, true) ?? [];
            elseif (strpos($type, 'application/x-www-form-urlencoded') !== false) parse_str($input, $cache);
            else $cache = $input;
        }
        if ($key === null) return $cache;
        return is_array($cache) ? ($cache[$key] ?? null) : null;
    }

    public function request(?string $key = null, ?string $method = null)
    {
        if ($method !== null) {
            $data = match (strtolower($method)) {
                'get' => $_GET, 'post' => $_POST, 'body' => $this->body(), default => [],
            };
            return $key ? ($data[$key] ?? null) : $data;
        }
        $body = $this->body();
        $data = array_merge($_GET ?? [], $_POST ?? [], is_array($body) ? $body : []);
        return $key ? ($data[$key] ?? null) : $data;
    }

    public function header($key = null, $value = null)
    {
        if ($key === 'destroy') { header_remove(); return $this; }
        if ($key === null) return getallheaders();
        if (is_array($key)) { foreach ($key as $k => $v) $this->header($k, $v); return $this; }
        if ($value === null) {
            $headers = getallheaders();
            $k = str_replace(' ', '-', ucwords(str_replace('-', ' ', $key)));
            return $headers[$k] ?? null;
        }
        if ($value === false) { header_remove($key); return $this; }
        header("{$key}: {$value}");
        return $this;
    }

    // ======================== CORS / SECURITY ========================

    public function cors(): self
    {
        if (!($this->config['cors']['on'] ?? false)) return $this;
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        $allowed = $this->config['cors']['origins'] ?? [];
        if (!empty($allowed) && $allowed !== '*') {
            if (!in_array($origin, (array)$allowed, true)) $origin = is_array($allowed) ? ($allowed[0] ?? '') : $allowed;
        }
        $this->header([
            'Access-Control-Allow-Origin'  => $origin,
            'Access-Control-Allow-Methods' => $this->config['cors']['methods'] ?? 'GET, POST, PUT, DELETE, OPTIONS, PATCH',
            'Access-Control-Allow-Headers' => $this->config['cors']['headers'] ?? 'Content-Type, Authorization, X-Csrf-Token',
        ]);
        if ($this->config['cors']['credentials'] ?? false) {
            $this->header([
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Allow-Origin' => $origin === '*' ? ($_SERVER['HTTP_ORIGIN'] ?? '') : $origin,
            ]);
        }
        return $this;
    }

    private function send_headers(): void
    {
        if (headers_sent()) return;
        foreach ([
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'X-Permitted-Cross-Domain-Policies' => 'none',
        ] as $k => $v) header("{$k}: {$v}");

        $csp = $this->config['csp'] ?? null;
        if ($csp) header("Content-Security-Policy: {$csp}");
    }

    // ======================== CSRF ========================

    public function csrf($arg = null)
    {
        $this->session(null, null);
        if ($arg === null) {
            if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
            return $_SESSION['csrf'];
        }
        if ($arg === true) {
            return '<input type="hidden" name="csrf" value="' . htmlspecialchars($this->csrf(), ENT_QUOTES, 'UTF-8') . '">';
        }
        if (is_string($arg)) {
            if (empty($_SESSION['csrf'])) return false;
            return hash_equals($_SESSION['csrf'], $arg);
        }
        return false;
    }

    public function csrf_check($token = null): bool
    {
        $this->session(null, null);
        $token = $token ?? $this->request('csrf') ?? $this->header('X-Csrf-Token');
        if (empty($_SESSION['csrf']) || empty($token)) return false;
        return hash_equals($_SESSION['csrf'], $token);
    }

    // ======================== RATE LIMIT ========================

    private function rate(string $key): void
    {
        $max = $this->config['security']['max'] ?? 60;
        $window = $this->config['security']['window'] ?? 60;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $file = $this->storage('cache') . '/rate_' . md5($ip . '_' . $key) . '.cache';

        $times = [];
        if (file_exists($file)) $times = json_decode(file_get_contents($file), true) ?: [];
        $now = time();
        $times = array_values(array_filter($times, fn($t) => $t > ($now - $window)));
        $times[] = $now;
        file_put_contents($file, json_encode($times), LOCK_EX);

        if (count($times) > $max) {
            $this->header('Retry-After', $window);
            $this->response(['error' => true, 'message' => 'Too many requests'], 'json', 429);
            exit;
        }
    }

    // ======================== PASSWORD / TOKEN ========================

    public function hash(string $password): string { return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]); }
    public function check(string $password, string $hash): bool { return password_verify($password, $hash); }

    public function token(): ?string
    {
        $h = $this->header('Authorization');
        return ($h && strpos($h, 'Bearer ') === 0) ? substr($h, 7) : null;
    }

    // ======================== JWT ========================

    /**
     * JWT: encode or decode/verify.
     *
     * $app->jwt(['user' => 1])            → signed token string
     * $app->jwt(['user' => 1], 7200)      → with custom expire (seconds)
     * $app->jwt($token)                   → decode + verify, returns payload or null
     */
    public function jwt($data, ?int $expire = null)
    {
        $secret = $this->config['jwt']['secret'] ?? $this->config['app_key'] ?? 'trindade';

        // Decode / verify
        if (is_string($data)) {
            return $this->jwt_decode($data, $secret);
        }

        // Encode
        $expire = $expire ?? $this->config['jwt']['expire'] ?? 3600;
        return $this->jwt_encode($data, $secret, $expire);
    }

    private function jwt_encode(array $payload, string $secret, int $expire): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload['iat'] = time();
        $payload['exp'] = time() + $expire;

        $b64 = fn($d) => rtrim(strtr(base64_encode(json_encode($d)), '+/', '-_'), '=');

        $data = $b64($header) . '.' . $b64($payload);
        $sig = rtrim(strtr(base64_encode(hash_hmac('sha256', $data, $secret, true)), '+/', '-_'), '=');

        return $data . '.' . $sig;
    }

    private function jwt_decode(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        [$h, $p, $sig] = $parts;
        $b64d = fn($d) => json_decode(base64_decode(strtr($d, '-_', '+/')), true);

        $header = $b64d($h);
        $payload = $b64d($p);
        if (!$header || !$payload) return null;

        $data = $h . '.' . $p;
        $expected = rtrim(strtr(base64_encode(hash_hmac('sha256', $data, $secret, true)), '+/', '-_'), '=');

        if (!hash_equals($expected, $sig)) return null;
        if (($payload['exp'] ?? 0) < time()) return null;

        return $payload;
    }

    // ======================== SESSION ========================

    public function session($key = null, $value = null)
    {
        if (!session_id()) session_start();

        if ($key === 'destroy') {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $p = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
            }
            session_destroy();
            return $this;
        }
        if ($key === null) return $_SESSION;
        if (is_array($key)) { foreach ($key as $k => $v) $_SESSION[$k] = $v; return $this; }
        if ($value === null) return $_SESSION[$key] ?? null;
        if ($value === false) { unset($_SESSION[$key]); return $this; }
        $_SESSION[$key] = $value;
        return $this;
    }

    public function session_refresh(): self
    {
        if (session_id()) session_regenerate_id(true);
        return $this;
    }

    public function flash(string $key, $value = null)
    {
        if ($value === null) { $v = $this->session($key); $this->session($key, false); return $v; }
        return $this->session($key, $value);
    }

    // ======================== COOKIE ========================

    public function cookie($key = null, $value = null, array $opts = [])
    {
        if ($key === 'destroy') {
            foreach ($_COOKIE as $k => $v) {
                setcookie($k, '', ['expires' => time() - 3600, 'path' => '/']);
                unset($_COOKIE[$k]);
            }
            return $this;
        }
        if ($key === null) return $_COOKIE;
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                if (is_array($v) && isset($v['value'])) $this->cookie($k, $v['value'], $v);
                else $this->cookie($k, $v, $opts);
            }
            return $this;
        }
        if ($value === null) return $_COOKIE[$key] ?? null;
        if ($value === false) { setcookie($key, '', ['expires' => time() - 3600, 'path' => '/']); unset($_COOKIE[$key]); return $this; }

        $o = array_merge(['expires' => 0, 'path' => '/', 'domain' => '', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax'], $opts);
        setcookie($key, $value, $o);
        $_COOKIE[$key] = $value;
        return $this;
    }

    // ======================== CACHE / STORAGE ========================


    public function clear(string $type = 'cache'): self
    {
        $path = $this->storage($type);
        if (is_dir($path)) foreach (glob($path . '/*') as $f) if (is_file($f)) unlink($f);
        $this->log("Cleared: {$type}", "info");
        return $this;
    }

    public function cleanup(int $max = 86400): self
    {
        foreach (['cache', 'temp'] as $type) {
            $path = $this->storage($type);
            if (!is_dir($path)) continue;
            foreach (new \DirectoryIterator($path) as $f) {
                if ($f->isDot()) continue;
                if ($f->getMTime() < time() - $max) unlink($f->getPathname());
            }
        }
        return $this;
    }

    public function storage(string $type = 'root'): string
    {
        if ($type === 'root') return dirname($this->paths['storage']['cache']) ?: $this->paths['root'];
        return $this->paths['storage'][$type] ?? $this->paths['root'];
    }

    public function move(string $from, string $to, string $type = 'app'): bool
    {
        $src = $this->storage($type) . '/' . $from;
        $dst = $this->storage($type) . '/' . $to;
        if (!file_exists($src)) return false;
        if (!is_dir(dirname($dst))) mkdir(dirname($dst), $this->perm['folder'], true);
        if (rename($src, $dst)) { chmod($dst, is_file($dst) ? $this->perm['public'] : $this->perm['folder']); return true; }
        return false;
    }

    // ======================== UPLOAD / DOWNLOAD ========================

    public function upload(string $field, ?string $path = null): string|false
    {
        if (!isset($_FILES[$field])) return false;
        $file = $_FILES[$field];
        $path = $path ?? $this->storage('uploads');

        $base = realpath($this->storage('uploads'));
        $dest = realpath($path) ?: $path;
        if ($base && strpos($dest, $base) !== 0) { $this->log("Upload rejected: bad path", "error"); return false; }
        if (!is_dir($path)) return false;

        $max = $this->config['upload']['max_size'] ?? 5242880;
        if ($file['size'] > $max) { $this->log("Upload rejected: too large", "error"); return false; }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = $this->config['upload']['extensions'] ?? ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'webp'];
        $blocked = ['php','phtml','php3','php4','php5','php7','php8','phar','pl','py','cgi','asp','aspx','jsp','sh','bash','exe','bat','cmd','dll','so'];
        if (in_array($ext, $blocked, true)) { $this->log("Upload rejected: .{$ext}", "error"); return false; }
        if (!empty($allowed) && !in_array($ext, $allowed, true)) { $this->log("Upload rejected: .{$ext} not allowed", "error"); return false; }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $types = $this->config['upload']['types'] ?? ['image/jpeg', 'image/png', 'application/pdf'];
        if (!in_array($mime, $types, true)) { $this->log("Upload rejected: {$mime}", "error"); return false; }

        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        $full = $path . '/' . $name;
        if (move_uploaded_file($file['tmp_name'], $full)) { @chmod($full, $this->perm['public']); return $name; }
        return false;
    }

    public function download(string $file, ?string $name = null): void
    {
        if (strpos(str_replace('\\', '/', $file), '../') !== false) { http_response_code(403); exit; }
        $path = $file;
        if (!file_exists($path)) $path = $this->storage('uploads') . '/' . ltrim($file, '/');
        if (!file_exists($path) || !is_file($path)) { http_response_code(404); exit; }

        $real = realpath($path);
        $ok = false;
        foreach ([realpath($this->storage('uploads')), realpath($this->storage('temp'))] as $d) {
            if ($d && strpos($real, $d) === 0) { $ok = true; break; }
        }
        if (!$ok) { http_response_code(403); exit; }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . htmlspecialchars($name ?? basename($path), ENT_QUOTES, 'UTF-8') . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        readfile($path);
        exit;
    }

    // ======================== VALIDATE / SANITIZE ========================

    public function validate(array $rules, ?array $data = null)
    {
        $data = $data ?? $this->request();
        $errors = []; $clean = [];

        foreach ($rules as $field => $rule) {
            $parts = is_array($rule) ? $rule : explode('|', $rule);
            $value = $data[$field] ?? null;

            foreach ($parts as $r) {
                if (is_callable($r)) { if (!$r($value)) $errors[$field][] = "{$field} validation failed"; continue; }
                if ($r === 'required' && ($value === null || $value === '')) { $errors[$field][] = "{$field} is required"; continue; }
                if ($value === null && $r !== 'required' && !str_starts_with($r, 'required_with:')) continue;

                // string rules
                if (strpos($r, 'min:') === 0) { $min = (int)substr($r, 4); if (is_string($value) && mb_strlen($value) < $min) $errors[$field][] = "{$field} min {$min}"; }
                elseif (strpos($r, 'max:') === 0) { $max = (int)substr($r, 4); if (is_string($value) && mb_strlen($value) > $max) $errors[$field][] = "{$field} max {$max}"; }
                elseif ($r === 'string') { if (!is_string($value)) $errors[$field][] = "{$field} must be a string"; }
                elseif ($r === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) $errors[$field][] = "{$field} invalid email";
                elseif ($r === 'numeric' && !is_numeric($value)) $errors[$field][] = "{$field} must be numeric";
                elseif ($r === 'integer' && !is_int($value) && !ctype_digit((string)$value)) $errors[$field][] = "{$field} must be an integer";
                elseif ($r === 'url' && !filter_var($value, FILTER_VALIDATE_URL)) $errors[$field][] = "{$field} invalid URL";
                elseif ($r === 'boolean') { if (!in_array($value, [true, false, 0, 1, '0', '1'], true)) $errors[$field][] = "{$field} must be boolean"; }
                elseif ($r === 'array' && !is_array($value)) $errors[$field][] = "{$field} must be an array";
                elseif ($r === 'date' && !strtotime((string)$value)) $errors[$field][] = "{$field} invalid date";
                elseif ($r === 'json') { json_decode((string)$value); if (json_last_error() !== JSON_ERROR_NONE) $errors[$field][] = "{$field} invalid JSON"; }
                elseif ($r === 'alpha') { if (!ctype_alpha((string)$value)) $errors[$field][] = "{$field} must be alpha"; }
                elseif ($r === 'alphanumeric') { if (!ctype_alnum((string)$value)) $errors[$field][] = "{$field} must be alphanumeric"; }

                // confirmed: checks field_confirmation
                elseif ($r === 'confirmed') { if ($value !== ($data[$field . '_confirmation'] ?? null)) $errors[$field][] = "{$field} does not match confirmation"; }

                // in:value1,value2,...
                elseif (strpos($r, 'in:') === 0) { $allowed = explode(',', substr($r, 3)); if (!in_array($value, $allowed, true)) $errors[$field][] = "{$field} must be one of: " . implode(', ', $allowed); }

                // not_in:value1,value2
                elseif (strpos($r, 'not_in:') === 0) { $blocked = explode(',', substr($r, 7)); if (in_array($value, $blocked, true)) $errors[$field][] = "{$field} cannot be: " . implode(', ', $blocked); }

                // exists:table,column — checks DB
                elseif (strpos($r, 'exists:') === 0 && $this->db) { [$t, $c] = explode(',', substr($r, 7)) + [1 => 'id']; if (!$this->db->has($t, [$c => $value])) $errors[$field][] = "{$field} not found"; }

                // unique:table,column,except_id
                elseif (strpos($r, 'unique:') === 0 && $this->db) { $parts = explode(',', substr($r, 7)); $t = $parts[0]; $c = $parts[1] ?? $field; $except = $parts[2] ?? null; $where = [$c => $value]; if ($except) $where['id[!]'] = (int)$except; if ($this->db->has($t, $where)) $errors[$field][] = "{$field} already exists"; }

                // required_with:other_field
                elseif (strpos($r, 'required_with:') === 0) { $other = substr($r, 15); if (!empty($data[$other]) && empty($value)) $errors[$field][] = "{$field} is required with {$other}"; }

                // before:date, after:date
                elseif (strpos($r, 'before:') === 0) { $d = substr($r, 7); if (strtotime((string)$value) >= strtotime($d)) $errors[$field][] = "{$field} must be before {$d}"; }
                elseif (strpos($r, 'after:') === 0) { $d = substr($r, 6); if (strtotime((string)$value) <= strtotime($d)) $errors[$field][] = "{$field} must be after {$d}"; }

                // same:other_field
                elseif (strpos($r, 'same:') === 0) { $other = substr($r, 5); if ($value !== ($data[$other] ?? null)) $errors[$field][] = "{$field} must match {$other}"; }

                // different:other_field
                elseif (strpos($r, 'different:') === 0) { $other = substr($r, 10); if ($value === ($data[$other] ?? null)) $errors[$field][] = "{$field} must differ from {$other}"; }

                // regex:pattern
                elseif (strpos($r, 'regex:') === 0) { if (!preg_match(substr($r, 6), (string)$value)) $errors[$field][] = "{$field} format invalid"; }

                // starts_with:prefix, ends_with:suffix
                elseif (strpos($r, 'starts_with:') === 0) { $pfx = substr($r, 12); if (!str_starts_with((string)$value, $pfx)) $errors[$field][] = "{$field} must start with {$pfx}"; }
                elseif (strpos($r, 'ends_with:') === 0) { $sfx = substr($r, 10); if (!str_ends_with((string)$value, $sfx)) $errors[$field][] = "{$field} must end with {$sfx}"; }

                // file / image validation
                elseif ($r === 'file') { if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) $errors[$field][] = "{$field} must be a valid file"; }
                elseif ($r === 'image') { if (!isset($_FILES[$field]) || !str_starts_with($_FILES[$field]['type'] ?? '', 'image/')) $errors[$field][] = "{$field} must be an image"; }
                elseif (strpos($r, 'mimes:') === 0) { $allowed = explode(',', substr($r, 6)); $ext = strtolower(pathinfo($_FILES[$field]['name'] ?? '', PATHINFO_EXTENSION)); if (!in_array($ext, $allowed)) $errors[$field][] = "{$field} type must be: " . implode(', ', $allowed); }
                elseif (strpos($r, 'size:') === 0) { $max = (int)substr($r, 5) * 1024; if (($_FILES[$field]['size'] ?? 0) > $max) $errors[$field][] = "{$field} max " . ($max/1024) . "KB"; }
            }
            if (!isset($errors[$field]) && isset($data[$field])) $clean[$field] = $this->sanitize($value);
        }
        return empty($errors) ? $clean : $this->error(['errors' => $errors], 422);
    }

    public function sanitize($value)
    {
        if (is_array($value)) return array_map([$this, 'sanitize'], $value);
        if (is_string($value)) return trim(strip_tags($value));
        return $value;
    }

    // ======================== LOG / CONFIG / PATH ========================

    public function log(string $msg, string $level = 'info'): self
    {
        if (!isset($this->paths['storage']['logs'])) return $this;
        $file = $this->paths['storage']['logs'] . '/app.log';
        $clean = str_replace(["\n", "\r"], ' ', $msg);
        @file_put_contents($file, "[" . date('Y-m-d H:i:s') . "] [{$level}] {$clean}" . PHP_EOL, FILE_APPEND | LOCK_EX);
        return $this;
    }

    public function config(?string $key = null) { return $key ? ($this->config[$key] ?? null) : $this->config; }

    public function path(?string $key = null)
    {
        if ($key === null) return $this->paths;
        if (strpos($key, '.') !== false) { [$s, $sub] = explode('.', $key); return $this->paths[$s][$sub] ?? null; }
        return $this->paths[$key] ?? null;
    }

    // ======================== LOAD ========================

    public function load(string $type): self
    {
        $base = $this->paths[$type] ?? null;
        if (!$base || !($base = realpath($base)) || !is_dir($base)) return $this;
        foreach (scandir($base) as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                try { $app = $this; require_once $base . DIRECTORY_SEPARATOR . $file; }
                catch (\Throwable $e) { $this->log("Load fail {$file}: " . $e->getMessage(), "error"); }
            }
        }
        return $this;
    }

    // ======================== UTILITY ========================

    public function ago(string $date): string
    {
        $diff = time() - strtotime($date);
        if ($diff < 60) return 'just now';
        foreach (['year' => 31536000, 'month' => 2592000, 'week' => 604800, 'day' => 86400, 'hour' => 3600, 'minute' => 60, 'second' => 1] as $n => $s) {
            $c = floor($diff / $s);
            if ($c > 0) return "{$c} {$n}" . ($c > 1 ? 's' : '') . ' ago';
        }
        return 'just now';
    }

    public function random(int $length = 16, string $type = 'alphanumeric'): string
    {
        $chars = match ($type) { 'alpha' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 'numeric' => '0123456789', default => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789' };
        $max = strlen($chars) - 1; $out = '';
        for ($i = 0; $i < $length; $i++) $out .= $chars[random_int(0, $max)];
        return $out;
    }

    public function slug(string $text): string { return trim(preg_replace('/-+/', '-', preg_replace('/[^a-z0-9-]/', '-', strtolower($text))), '-'); }

    public function distance(float $lat1, float $lon1, float $lat2, float $lon2, string $unit = 'K'): float
    {
        if ($lat1 == $lat2 && $lon1 == $lon2) return 0;
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $miles = rad2deg(acos($dist)) * 60 * 1.1515;
        return round(match (strtoupper($unit)) { 'K' => $miles * 1.609344, 'N' => $miles * 0.8684, default => $miles }, 2);
    }

    public function clean(string $string, $chars = []): string
    {
        if (is_string($chars)) $chars = str_split($chars);
        if (empty($chars)) $chars = ['!','@','#','$','%','^','&','*','(',')','+','=','{','}','[',']',':',';','"',"'",'<','>','?','/','\\','|','`','~'];
        return str_replace($chars, '', $string);
    }

    public function import(string $url, array $options = [])
    {
        try {
            $o = array_merge(['method' => 'GET', 'headers' => [], 'data' => null, 'timeout' => 30, 'ssl' => true, 'json' => true], $options);
            $ch = curl_init();
            curl_setopt_array($ch, [CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $o['timeout'], CURLOPT_CUSTOMREQUEST => strtoupper($o['method'])]);
            if (!$o['ssl']) { curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); }
            if (!empty($o['headers'])) { $h = []; foreach ($o['headers'] as $k => $v) $h[] = "{$k}: {$v}"; curl_setopt($ch, CURLOPT_HTTPHEADER, $h); }
            if ($o['data']) curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($o['data']) ? http_build_query($o['data']) : $o['data']);
            $r = curl_exec($ch); $err = curl_error($ch); curl_close($ch);
            if ($err) throw new \Exception("API error: {$err}");
            return $o['json'] ? json_decode($r, true) : $r;
        } catch (\Throwable $e) { $this->log("Import: " . $e->getMessage(), "error"); return ['error' => 'API request failed']; }
    }

    // ======================== QR CODE ========================

    /**
     * Generate a QR code PNG image.
     * Built-in, zero dependencies. Uses GD.
     *
     * $app->qr('https://example.com')          → base64 data URI
     * $app->qr('hello', 300)                   → custom size
     * $app->qr('data', 200, 'qrcode.png')      → save to file
     */
    public function qr(string $data, int $size = 200, ?string $file = null, int $margin = 2): string
    {
        $matrix = $this->qr_matrix($data);
        $img = $this->qr_render($matrix, $size, $margin);

        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);

        if ($file) {
            file_put_contents($file, $png);
            return $file;
        }

        return 'data:image/png;base64,' . base64_encode($png);
    }

    private function qr_matrix(string $data): array
    {
        $bytes = array_map('ord', str_split($data));
        $len = count($bytes);
        $v = $len <= 17 ? 1 : ($len <= 32 ? 2 : ($len <= 53 ? 3 : ($len <= 78 ? 4 : ($len <= 106 ? 5 : 6))));

        $ec = [
            1 => [0 => [26,19,0], 1 => [26,16,0], 2 => [26,13,0], 3 => [26,9,0]],
            2 => [0 => [44,34,0], 1 => [44,28,0], 2 => [44,22,0], 3 => [44,16,0]],
            3 => [0 => [70,55,0], 1 => [70,44,0], 2 => [70,34,1], 3 => [70,26,1]],
            4 => [0 => [100,80,0], 1 => [100,64,0], 2 => [100,48,0], 3 => [100,36,1]],
            5 => [0 => [134,108,0], 1 => [134,86,0], 2 => [134,62,0], 3 => [134,46,0]],
            6 => [0 => [172,136,0], 1 => [172,108,0], 2 => [172,76,1], 3 => [172,60,0]],
        ][$v][1]; // M level (index 1)

        [$total, $data_words, $ec_blocks] = $ec;
        $size = 17 + $v * 4;
        $matrix = array_fill(0, $size, array_fill(0, $size, -1));

        // Bit stream: mode(4) + count(8) + data + terminator + pad
        $bits = '';
        $bits .= str_pad(decbin(4), 4, '0', STR_PAD_LEFT); // byte mode
        $bits .= str_pad(decbin($len), 8, '0', STR_PAD_LEFT);
        foreach ($bytes as $b) $bits .= str_pad(decbin($b), 8, '0', STR_PAD_LEFT);
        $bits .= '0000'; // terminator
        while (strlen($bits) % 8 !== 0) $bits .= '0'; // byte align
        $pad = ['11101100', '00010001']; $pi = 0;
        while (strlen($bits) < $data_words * 8) { $bits .= $pad[$pi]; $pi = ($pi + 1) % 2; }

        $words = [];
        for ($i = 0; $i < strlen($bits); $i += 8) $words[] = bindec(substr($bits, $i, 8));
        $words = array_pad($words, $data_words, 0);

        // Error correction (Reed-Solomon)
        $ecw = $this->qr_ec($words, $data_words + $ec_blocks);
        $all = array_merge($words, $ecw);

        // Finder patterns
        $this->qr_finder($matrix, 0, 0);
        $this->qr_finder($matrix, $size - 7, 0);
        $this->qr_finder($matrix, 0, $size - 7);

        // Timing
        for ($i = 8; $i < $size - 8; $i++) {
            $matrix[6][$i] = $matrix[$i][6] = ($i % 2 === 0) ? 1 : 0;
        }

        // Alignment (version >= 2)
        if ($v >= 2) {
            $aps = [[6, 6], [6, 18], [18, 6], [18, 18], [6, 22], [22, 6], [22, 22]];
            $pos = $v >= 2 ? [6, $size - 7 - 2] : [];
            foreach ($pos as $r) foreach ($pos as $c) {
                if (($r === 6 && $c === 6) || ($r === 6 && $c === $size - 7) || ($r === $size - 7 && $c === 6)) continue;
                $this->qr_align($matrix, $r, $c);
            }
        }

        // Format info
        $fmt = 0; // mask 0, M level
        $fmt = $fmt | (1 << 12); // M = 00 → complement
        $fmt = $fmt ^ 0x5412; // BCH
        for ($i = 0; $i < 15; $i++) {
            $bit = ($fmt >> $i) & 1;
            if ($i < 6) { $matrix[8][($i < 8 ? $i : 8) < 9 ? ($i < 6 ? $i : $i + 1) : 8 - ($i - 7)] = $bit; }
        }

        // Data placement
        $this->qr_place($matrix, $all, $data_words + $ec_blocks);

        // Mask 0: (row + col) % 2 == 0
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                if ($matrix[$r][$c] === -1) $matrix[$r][$c] = 0;
                elseif ($matrix[$r][$c] === 2) $matrix[$r][$c] = (($r + $c) % 2 === 0) ? 0 : 1;
                elseif ($matrix[$r][$c] === 3) $matrix[$r][$c] = (($r + $c) % 2 === 0) ? 1 : 0;
            }
        }

        return $matrix;
    }

    private function qr_finder(array &$m, int $r, int $c): void
    {
        for ($dr = -1; $dr <= 7; $dr++) {
            for ($dc = -1; $dc <= 7; $dc++) {
                $pr = $r + $dr; $pc = $c + $dc;
                if ($pr < 0 || $pc < 0 || $pr >= count($m) || $pc >= count($m[0])) continue;
                $m[$pr][$pc] = ($dr >= 0 && $dr <= 6 && $dc >= 0 && $dc <= 6)
                    ? (($dr === 0 || $dr === 6 || $dc === 0 || $dc === 6) ? 1 :
                      (($dr >= 2 && $dr <= 4 && $dc >= 2 && $dc <= 4) ? 1 : 0))
                    : 0;
            }
        }
    }

    private function qr_align(array &$m, int $r, int $c): void
    {
        for ($dr = -2; $dr <= 2; $dr++) {
            for ($dc = -2; $dc <= 2; $dc++) {
                $pr = $r + $dr; $pc = $c + $dc;
                if ($pr < 0 || $pc < 0 || $pr >= count($m) || $pc >= count($m[0])) continue;
                $m[$pr][$pc] = ($dr === -2 || $dr === 2 || $dc === -2 || $dc === 2 || ($dr === 0 && $dc === 0)) ? 1 : 0;
            }
        }
    }

    private function qr_place(array &$m, array $data, int $total): void
    {
        $size = count($m);
        $bits = '';
        for ($i = 0; $i < $total; $i++) {
            $bits .= str_pad(decbin($data[$i] ?? 0), 8, '0', STR_PAD_LEFT);
        }

        $c = $size - 1; $r = $size - 1; $up = true; $bi = 0;
        while ($c > 0) {
            $cols = $c === 6 ? [5, 4] : [$c, $c - 1];
            foreach ($cols as $col) {
                for ($row = $up ? $size - 1 : 0; $up ? ($row >= 0) : ($row < $size); $up ? $row-- : $row++) {
                    if ($m[$row][$col] === -1 && $bi < strlen($bits)) {
                        $m[$row][$col] = 2 + (int)($bits[$bi] ?? '0');
                        $bi++;
                    }
                }
                $up = !$up;
            }
            $c -= 2;
        }
    }

    private function qr_ec(array $data, int $total): array
    {
        $gen = $this->qr_genpoly($total - count($data));
        $msg = array_pad($data, $total, 0);

        for ($i = 0; $i < count($data); $i++) {
            if ($msg[$i] === 0) continue;
            $factor = $this->qr_glog($msg[$i]);
            for ($j = 0; $j < count($gen); $j++) {
                $msg[$i + $j] ^= $this->qr_gexp(($gen[$j] + $factor) % 255);
            }
        }

        return array_slice($msg, count($data));
    }

    private function qr_genpoly(int $count): array
    {
        $poly = [1];
        for ($i = 0; $i < $count; $i++) {
            $poly[] = 0;
            for ($j = count($poly) - 1; $j > 0; $j--) {
                $poly[$j] = $poly[$j - 1] ^ $this->qr_gexp(($this->qr_glog($poly[$j]) + $i) % 255);
            }
            $poly[0] = $this->qr_gexp(($this->qr_glog($poly[0]) + $i) % 255);
        }
        return $poly;
    }

    private function qr_glog(int $n): int
    {
        static $log = null;
        if ($log === null) {
            $log = [1 => 0]; $v = 1;
            for ($i = 1; $i < 255; $i++) { $v = ($v * 2) ^ (($v & 128) ? 0x11d : 0); $log[$v] = $i; }
        }
        return $log[$n] ?? 0;
    }

    private function qr_gexp(int $n): int
    {
        static $exp = null;
        if ($exp === null) {
            $exp = [0 => 1]; $v = 1;
            for ($i = 1; $i < 255; $i++) { $v = ($v * 2) ^ (($v & 128) ? 0x11d : 0); $exp[$i] = $v; }
        }
        return $exp[$n % 255] ?? 0;
    }

    private function qr_render(array $matrix, int $size, int $margin): \GdImage
    {
        $mod_count = count($matrix);
        $total = $mod_count + 2 * $margin;
        $scale = (int)floor($size / $total);
        $img_size = $total * $scale;
        $img = imagecreatetruecolor($img_size, $img_size);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $white);

        for ($r = 0; $r < $mod_count; $r++) {
            for ($c = 0; $c < $mod_count; $c++) {
                if (($matrix[$r][$c] ?? 0) === 1) {
                    imagefilledrectangle($img,
                        ($c + $margin) * $scale, ($r + $margin) * $scale,
                        ($c + $margin + 1) * $scale - 1, ($r + $margin + 1) * $scale - 1,
                        $black);
                }
            }
        }

        return $img;
    }

    // ======================== NIS2 COMPLIANCE ========================

    /**
     * Audit trail — immutable log of sensitive actions.
     *
     * $app->audit('user.login', ['user' => 1, 'ip' => $_SERVER['REMOTE_ADDR']])
     * $app->audit('data.export', ['user' => 1, 'records' => 42])
     */
    public function audit(string $action, array $data = []): self
    {
        $file = ($this->paths['storage']['logs'] ?? '/tmp') . '/audit.log';
        $entry = [
            'ts'     => date('c'),
            'action' => $action,
            'ip'     => $_SERVER['REMOTE_ADDR'] ?? 'cli',
            'user'   => $this->session('user_id'),
            'data'   => $data,
        ];
        @file_put_contents($file, json_encode($entry) . "\n", FILE_APPEND | LOCK_EX);
        return $this;
    }

    /**
     * Read audit log (for Studio/admin).
     */
    public function audit_log(int $lines = 100): array
    {
        $file = ($this->paths['storage']['logs'] ?? '/tmp') . '/audit.log';
        if (!file_exists($file)) return [];
        $all = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $all = array_slice($all, -$lines);
        return array_map(fn($l) => json_decode($l, true), $all);
    }

    /**
     * TOTP — Time-based One-Time Password (RFC 6238).
     *
     * $secret = $app->totp()               → generate new secret (base32)
     * $code   = $app->totp($secret)        → current 6-digit code
     * $valid  = $app->totp($secret, $code) → verify code
     */
    public function totp($secret = null, $code = null)
    {
        if ($secret === null) {
            return $this->totp_secret();
        }

        if ($code === null) {
            return $this->totp_code($secret);
        }

        return hash_equals((string)$this->totp_code($secret), (string)$code);
    }

    private function totp_secret(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $out = '';
        for ($i = 0; $i < 16; $i++) $out .= $chars[random_int(0, 31)];
        return $out;
    }

    private function totp_code(string $secret, int $window = 30, int $digits = 6): int
    {
        $counter = (int)floor(time() / $window);
        $secret = $this->totp_base32_decode($secret);
        $counter = pack('J', $counter);
        $hash = hash_hmac('sha1', $counter, $secret, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $value = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;
        return $value % (10 ** $digits);
    }

    private function totp_base32_decode(string $s): string
    {
        static $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $s = strtoupper(str_replace(' ', '', $s));
        $buffer = 0; $bits = 0; $out = '';
        for ($i = 0; $i < strlen($s); $i++) {
            $val = strpos($alphabet, $s[$i]);
            if ($val === false) continue;
            $buffer = ($buffer << 5) | $val;
            $bits += 5;
            if ($bits >= 8) {
                $bits -= 8;
                $out .= chr(($buffer >> $bits) & 0xFF);
            }
        }
        return $out;
    }

    /**
     * Encrypt / decrypt data with AES-256-GCM.
     *
     * $cipher = $app->encrypt('sensitive data')
     * $text   = $app->decrypt($cipher)
     *
     * Key: config 'app_key' or auto-generated.
     */
    public function encrypt(string $data): string
    {
        $key = $this->nis2_key();
        $iv = random_bytes(12);
        $tag = '';
        $encrypted = openssl_encrypt($data, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        return base64_encode($iv . $tag . $encrypted);
    }

    public function decrypt(string $data): ?string
    {
        $key = $this->nis2_key();
        $raw = base64_decode($data);
        if (strlen($raw) < 28) return null; // iv(12) + tag(16) min
        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $encrypted = substr($raw, 28);
        $result = openssl_decrypt($encrypted, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        return $result !== false ? $result : null;
    }

    /**
     * Derive encryption key from app_key.
     */
    private function nis2_key(): string
    {
        $raw = $this->config['app_key'] ?? 'trindade-fallback-key-do-not-use-in-production';
        if (strpos($raw, 'base64:') === 0) $raw = base64_decode(substr($raw, 7));
        return hash('sha256', $raw, true);
    }

    /**
     * Password policy check.
     *
     * $errors = $app->password_policy('myPassword123!')
     * Returns [] if valid, or array of error messages.
     */
    public function password_policy(string $password, array $rules = []): array
    {
        $rules = array_merge([
            'min'      => 8,
            'max'      => 128,
            'upper'    => true,
            'lower'    => true,
            'number'   => true,
            'special'  => true,
            'history'  => 0,      // number of previous hashes to check
        ], $rules);

        $errors = [];

        if (strlen($password) < $rules['min']) $errors[] = "At least {$rules['min']} characters";
        if (strlen($password) > $rules['max']) $errors[] = "Maximum {$rules['max']} characters";
        if ($rules['upper'] && !preg_match('/[A-Z]/', $password)) $errors[] = "At least one uppercase letter";
        if ($rules['lower'] && !preg_match('/[a-z]/', $password)) $errors[] = "At least one lowercase letter";
        if ($rules['number'] && !preg_match('/[0-9]/', $password)) $errors[] = "At least one number";
        if ($rules['special'] && !preg_match('/[^a-zA-Z0-9]/', $password)) $errors[] = "At least one special character";

        // Check against password history
        if ($rules['history'] > 0) {
            $history = $this->session('pwd_history') ?: [];
            foreach ($history as $old) {
                if (password_verify($password, $old)) {
                    $errors[] = "Password already used recently";
                    break;
                }
            }
        }

        return $errors;
    }

    /**
     * Record password in history after successful change.
     */
    public function password_history(string $hash, int $keep = 5): self
    {
        $history = $this->session('pwd_history') ?: [];
        array_unshift($history, $hash);
        $this->session('pwd_history', array_slice($history, 0, $keep));
        $this->audit('password.change');
        return $this;
    }

    /**
     * Account lockout — track failed attempts.
     *
     * $app->lockout('user@email.com')          → record failure, returns attempts count
     * $app->lockout('user@email.com', true)     → reset on success
     */
    public function lockout(string $identifier, bool $reset = false): int
    {
        $file = $this->storage('cache') . '/lockout_' . md5($identifier) . '.cache';

        if ($reset) {
            if (file_exists($file)) unlink($file);
            return 0;
        }

        $data = [];
        if (file_exists($file)) $data = json_decode(file_get_contents($file), true) ?: [];

        $max = $this->config['security']['lockout_max'] ?? 5;
        $window = $this->config['security']['lockout_window'] ?? 900; // 15 min

        $now = time();
        $data = array_values(array_filter($data, fn($t) => $t > ($now - $window)));
        $data[] = $now;
        file_put_contents($file, json_encode($data), LOCK_EX);

        if (count($data) >= $max) {
            $this->audit('account.lockout', ['identifier' => hash('sha256', $identifier)]);
        }

        return count($data);
    }

    /**
     * Content Security Policy headers.
     *
     * $app->csp("default-src 'self'; script-src 'self'")
     * $app->csp()  → default strict policy
     */
    public function csp(?string $policy = null): self
    {
        if ($policy === null) {
            $policy = "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; "
                    . "img-src 'self' data:; font-src 'self'; connect-src 'self'; "
                    . "frame-ancestors 'none'; base-uri 'self'; form-action 'self'";
        }

        header("Content-Security-Policy: {$policy}");
        header("X-Content-Security-Policy: {$policy}"); // legacy
        return $this;
    }

    /**
     * Backup — dump database or files.
     *
     * $app->backup()              → full backup to storage/backups/
     * $app->backup('db')          → database only
     * $app->backup('files')       → routes + views + helpers only
     */
    public function backup(string $type = 'full'): string|false
    {
        $dir = $this->storage('backups');
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $ts = date('Ymd-His');
        $zipFile = $dir . "/backup-{$ts}.zip";
        $zip = new \ZipArchive();

        if ($zip->open($zipFile, \ZipArchive::CREATE) !== true) {
            $this->log("Backup failed: cannot create zip", "error");
            return false;
        }

        // DB dump
        if ($type === 'full' || $type === 'db') {
            if ($this->db) {
                try {
                    $sql = $this->db_dump();
                    $zip->addFromString('database.sql', $sql);
                } catch (\Throwable $e) {
                    $this->log("Backup: DB dump failed - " . $e->getMessage(), "error");
                }
            }
        }

        // Files
        if ($type === 'full' || $type === 'files') {
            foreach (['routes', 'views', 'helpers', 'plugins'] as $d) {
                $p = $this->path($d);
                if (!$p || !is_dir($p)) continue;
                $this->zip_add($zip, $p, $d . '/');
            }
        }

        $zip->close();

        if (file_exists($zipFile)) {
            $this->audit('backup.create', ['type' => $type, 'size' => filesize($zipFile)]);
            $this->log("Backup created: {$zipFile}", "info");
            return $zipFile;
        }

        return false;
    }

    private function db_dump(): string
    {
        $sql = "-- Trindade Backup " . date('Y-m-d H:i:s') . "\n\n";
        $tables = [];

        try {
            $type = $this->db->type();
            if ($type === 'sqlite') {
                $tables = array_map(fn($r) => $r[0],
                    $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(\PDO::FETCH_NUM));
            } else {
                $tables = array_map(fn($r) => $r[0],
                    $this->db->query("SHOW TABLES")->fetchAll(\PDO::FETCH_NUM));
            }
        } catch (\Throwable $e) {}

        foreach ($tables as $table) {
            // Create table
            try {
                $create = $this->db->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_NUM);
                $sql .= ($create[1] ?? "-- no DDL for {$table}") . ";\n\n";
            } catch (\Throwable $e) {
                $sql .= "-- Could not dump DDL for {$table}\n\n";
            }

            // Data
            try {
                $rows = $this->db->query("SELECT * FROM `{$table}`")->fetchAll();
                if (!empty($rows)) {
                    $cols = array_keys($rows[0]);
                    $sql .= "INSERT INTO `{$table}` (`" . implode('`, `', $cols) . "`) VALUES\n";
                    $values = [];
                    foreach ($rows as $row) {
                        $vals = array_map(fn($v) => $v === null ? 'NULL' : $this->db->pdo()->quote($v), $row);
                        $values[] = '(' . implode(', ', $vals) . ')';
                    }
                    $sql .= implode(",\n", $values) . ";\n\n";
                }
            } catch (\Throwable $e) {}
        }

        return $sql;
    }

    private function zip_add(\ZipArchive $zip, string $path, string $prefix): void
    {
        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        ) as $file) {
            $local = $prefix . str_replace($path . '/', '', $file->getPathname());
            if ($file->isDir()) { $zip->addEmptyDir($local); }
            else { $zip->addFile($file->getPathname(), $local); }
        }
    }

    /**
     * Incident alert — send notification via mail or webhook.
     *
     * $app->alert('critical', 'Database down')
     * $app->alert('warning', 'Rate limit exceeded', ['ip' => '...'])
     *
     * Config: 'alerts' => ['mail' => 'admin@ex.com', 'webhook' => 'https://...']
     */
    public function alert(string $level, string $msg, array $context = []): self
    {
        $config = $this->config['alerts'] ?? [];
        $this->audit('alert.' . $level, array_merge(['msg' => $msg], $context));

        $payload = json_encode([
            'level'   => $level,
            'message' => $msg,
            'context' => $context,
            'ts'      => date('c'),
        ]);

        // Mail
        if (!empty($config['mail'])) {
            try {
                $this->mail
                    ->to($config['mail'])
                    ->subject("[Trindade] [{$level}] Alert")
                    ->message("<pre>{$payload}</pre>")
                    ->send();
            } catch (\Throwable $e) {
                $this->log("Alert mail failed: " . $e->getMessage(), "error");
            }
        }

        // Webhook
        if (!empty($config['webhook'])) {
            try {
                $this->import($config['webhook'], [
                    'method' => 'POST',
                    'data'   => $payload,
                    'headers' => ['Content-Type' => 'application/json'],
                ]);
            } catch (\Throwable $e) {
                $this->log("Alert webhook failed: " . $e->getMessage(), "error");
            }
        }

        return $this;
    }

    // ======================== DB ========================

    /**
     * Get database connection. Supports multiple named connections.
     *
     * $app->db()              → default connection
     * $app->db('logs')        → named connection from 'databases' config
     */
    public function db(?string $name = null): ?Database
    {
        if ($name) return $this->dbs[$name] ?? null;

        if ($this->db === null && isset($this->config['db'])) {
            try { $this->db = new Database($this->config['db']); $this->dbs['default'] = $this->db; }
            catch (\PDOException $e) { $this->log("DB error", "error"); $this->db = null; }
        }
        return $this->db;
    }

    /**
     * List all active database connections.
     */
    public function db_connections(): array { return array_keys($this->dbs); }

    // ======================== SUPERVISOR ========================

    /**
     * Supervisor — health check and system metrics.
     *
     * $app->supervisor()      → full health report
     */
    public function supervisor(): array
    {
        $routes = 0;
        foreach ($this->routes as $rs) $routes += count($rs);

        $dbs = [];
        foreach ($this->dbs as $name => $db) {
            try { $db->query("SELECT 1")->fetch(); $dbs[$name] = 'connected'; }
            catch (\Throwable $e) { $dbs[$name] = 'error: ' . $e->getMessage(); }
        }

        $storage = $this->storage();
        $size = 0;
        if (is_dir($storage)) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($storage, \RecursiveDirectoryIterator::SKIP_DOTS)) as $f) $size += $f->getSize();
        }

        $logs = [];
        $logFile = ($this->paths['storage']['logs'] ?? '') . '/app.log';
        if (file_exists($logFile)) {
            $lines = array_slice(file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -20);
            $logs['last_20'] = $lines;
            $logs['errors'] = count(array_filter($lines, fn($l) => stripos($l, '[error]') !== false));
            $logs['warnings'] = count(array_filter($lines, fn($l) => stripos($l, '[warning]') !== false));
        }

        return [
            'status'       => 'running',
            'php'          => PHP_VERSION,
            'uptime_sec'   => round(microtime(true) - $this->started, 1),
            'memory_mb'    => round(memory_get_usage(true) / 1048576, 1),
            'memory_peak'  => round(memory_get_peak_usage(true) / 1048576, 1),
            'routes'       => $routes,
            'databases'    => $dbs,
            'storage_mb'   => round($size / 1048576, 2),
            'debug'        => $this->config['debug'] ?? false,
            'logs'         => $logs,
        ];
    }

    // ======================== USER MANAGEMENT ========================

    /**
     * Create a user in the database.
     *
     * $app->user_create(['email' => 'a@a.com', 'password' => 'secret', 'name' => 'John', 'role' => 'admin'])
     */
    public function user_create(array $data): string|int|false
    {
        if (!$this->db) return false;
        if (empty($data['email']) || empty($data['password'])) return false;

        $exists = $this->db->has('users', ['email' => $data['email']]);
        if ($exists) return false;

        $data['password'] = $this->hash($data['password']);
        if (!isset($data['created_at'])) $data['created_at'] = date('Y-m-d H:i:s');
        if (!isset($data['updated_at'])) $data['updated_at'] = date('Y-m-d H:i:s');

        $id = $this->db->insert('users', $data);
        $this->audit('user.created', ['email' => $data['email'], 'id' => $id]);
        $this->emit('user.created', ['email' => $data['email'], 'id' => $id, 'role' => $data['role'] ?? 'user']);
        return $id;
    }

    /**
     * Delete a user by ID or email.
     */
    public function user_delete(int|string $identifier): bool
    {
        if (!$this->db) return false;
        $where = is_numeric($identifier) ? ['id' => (int)$identifier] : ['email' => $identifier];
        $this->db->delete('users', $where);
        $this->audit('user.deleted', ['identifier' => $identifier]);
        return true;
    }

    /**
     * List users with optional filter.
     */
    public function user_list(array $where = [], int $limit = 50): array
    {
        if (!$this->db) return [];
        return $this->db->select('users', ['id', 'email', 'name', 'role', 'created_at'], array_merge($where, ['LIMIT' => $limit]));
    }

    /**
     * Verify user credentials. Returns user array or null.
     */
    public function user_verify(string $email, string $password): ?array
    {
        if (!$this->db) return null;
        $user = $this->db->get('users', '*', ['email' => $email]);
        if (!$user) return null;
        if (!$this->check($password, $user['password'])) {
            $this->lockout($email);
            $this->audit('user.login_failed', ['email' => $email]);
            return null;
        }
        $this->lockout($email, true);
        $this->audit('user.login', ['email' => $email, 'id' => $user['id']]);
        $this->emit('user.login', ['email' => $email, 'id' => $user['id'], 'role' => $user['role'] ?? 'user']);
        unset($user['password']);
        return $user;
    }

    // ======================== DEBUG / DOCS ========================

    public function debug(string $msg, int $code = 500, ?string $details = null): void
    {
        if (php_sapi_name() === 'cli') {
            echo "\n  [ERROR {$code}] {$msg}\n";
            if ($details && ($this->config['debug'] ?? false)) echo "  {$details}\n";
            exit($code);
        }
        if (!headers_sent()) { http_response_code($code); header('Content-Type: text/html; charset=utf-8'); }

        // Custom error view: views/errors/404.php, views/errors/500.php, etc.
        $custom = $this->paths['views'] . '/errors/' . $code . '.php';
        if (file_exists($custom)) {
            $message = $msg;
            $trace = $details;
            require $custom;
            exit($code);
        }

        $is5xx = $code >= 500;
        $badgeClass = $is5xx ? 'e5' : 'e4';
        $title = self::ERROR_PAGES[$code] ?? ($is5xx ? 'An unexpected error occurred.' : 'Request could not be processed.');

        $showMsg = ($code < 500 || ($this->config['debug'] ?? false))
            ? htmlspecialchars($msg, ENT_QUOTES, 'UTF-8')
            : 'An unexpected error occurred. Please try again later.';

        $stackHtml = '';
        if (($this->config['debug'] ?? false) && $details) {
            $lines = explode("\n", $details);
            $formatted = '';
            foreach ($lines as $line) {
                $line = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
                if (preg_match('/#(\d+)\s+(.+?)\((\d+)\)/', $line, $m)) {
                    $formatted .= '<span class="file">#' . $m[1] . '</span> <span class="fn">' . $m[2] . '</span>:<span class="line">' . $m[3] . '</span>' . "\n";
                } elseif (preg_match('/#(\d+)\s+(.+)/', $line, $m)) {
                    $formatted .= '<span class="file">#' . $m[1] . '</span> ' . $m[2] . "\n";
                } elseif (preg_match('/thrown in (.+?) on line (\d+)/', $line, $m)) {
                    $formatted .= '<span class="file">' . $m[1] . '</span>:<span class="line">' . $m[2] . '</span>' . "\n";
                } else {
                    $formatted .= $line . "\n";
                }
            }
            $stackHtml = '<div class="trace-section"><div class="trace-label">Stack Trace</div><div class="trace">' . trim($formatted) . '</div></div>';
        }

        echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
           . '<title>' . $code . ' — Trindade</title><style>' . self::CSS . '</style></head><body>'
           . '<div class="wrap"><div class="card">'
           . '<div class="hdr"><div class="code-badge ' . $badgeClass . '">' . $code . '</div>'
           . '<div class="hdr-info"><h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>'
           . '<p>' . $showMsg . '</p></div></div>'
           . $stackHtml
           . '<div class="footer"><span><span class="dot"></span> Trindade ' . ($this->config['debug'] ?? false ? 'debug' : 'production') . '</span>'
           . '<span>PHP ' . PHP_VERSION . '</span></div>'
           . '</div></div></body></html>';
        exit($code);
    }

    public function docs(?string $path = null): string
    {
        $path = $path ?? $this->paths['routes'] ?? dirname(__DIR__) . '/routes';
        if (!is_dir($path)) return '<h1>No routes found</h1>';
        $docs = [];

        foreach (glob($path . '/*.php') as $file) {
            $c = @file_get_contents($file); if (!$c) continue;
            preg_match_all('/@api\s+(.*?)\s*\*\//s', $c, $m); if (empty($m[1])) continue;
            foreach ($m[1] as $block) {
                if (empty($block)) continue;
                $ep = [];
                foreach (explode("\n", $block) as $line) {
                    if (empty($line)) continue;
                    if (preg_match('/@api\s+{(\w+)}\s+([^\s]+)\s+(.*)/', $line, $x)) $ep = ['method' => $x[1], 'path' => $x[2], 'desc' => $x[3]];
                    if (preg_match('/@apiParam\s+{([^}]+)}\s+([^\s]+)\s+(.*)/', $line, $x)) { if (!isset($ep['params'])) $ep['params'] = []; $ep['params'][] = ['type' => $x[1], 'name' => $x[2], 'desc' => $x[3]]; }
                    if (preg_match('/@apiSuccess\s+{([^}]+)}\s+([^\s]+)\s+(.*)/', $line, $x)) { if (!isset($ep['success'])) $ep['success'] = []; $ep['success'][] = ['type' => $x[1], 'name' => $x[2], 'desc' => $x[3]]; }
                }
                if (!empty($ep)) $docs[] = $ep;
            }
        }

        $h = '<html><head><title>API Docs</title><style>' . self::CSS
           . '.ep{margin-bottom:2rem}.tag{display:inline-block;padding:3px 8px;border-radius:4px;color:#fff}'
           . '.tag.get{background:#61affe}.tag.post{background:#49cc90}.tag.put{background:#fca130}.tag.delete{background:#f93e3e}.tag.patch{background:#50e3c2}'
           . 'table{width:100%;border-collapse:collapse}td,th{padding:8px;border:1px solid #ddd}'
           . '</style></head><body><div class="box"><div class="body">';

        foreach ($docs as $ep) {
            $method = htmlspecialchars(strtolower($ep['method']), ENT_QUOTES, 'UTF-8');
            $h .= '<div class="ep"><h2><span class="tag ' . $method . '">' . strtoupper($method) . '</span> '
                . htmlspecialchars($ep['path'], ENT_QUOTES, 'UTF-8') . '</h2><p>' . htmlspecialchars($ep['desc'], ENT_QUOTES, 'UTF-8') . '</p>';
            if (!empty($ep['params'])) {
                $h .= '<h3>Params</h3><table><tr><th>Name</th><th>Type</th><th>Desc</th></tr>';
                foreach ($ep['params'] as $p) $h .= '<tr><td>' . htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') . '</td><td>' . htmlspecialchars($p['type'], ENT_QUOTES, 'UTF-8') . '</td><td>' . htmlspecialchars($p['desc'], ENT_QUOTES, 'UTF-8') . '</td></tr>';
                $h .= '</table>';
            }
            $h .= '</div>';
        }
        return $h . '</div></div></body></html>';
    }

    // ======================== MAGIC ========================

    public function __call(string $name, array $args)
    {
        if (isset($this->plugins[$name]) && is_callable($this->plugins[$name])) {
            return call_user_func_array($this->plugins[$name], $args);
        }
        if (!isset($this->{$name})) throw new \Exception("{$name}() not found");
        if (!is_callable($this->{$name})) throw new \Exception("{$name} is not callable");
        return call_user_func_array($this->{$name}, $args);
    }

    public function __set(string $name, $value): void { $this->{$name} = $value; }
    public function __get(string $name) { return $this->plugins[$name] ?? $this->{$name} ?? null; }

    // ======================== TESTING ========================

    /**
     * HTTP testing helper.
     *
     * $app->test('GET /users')->assertOk()->assertJson();
     * $app->test('POST /users', ['name' => 'John'])->assertCreated();
     */
    public function test(string $route, array $data = []): TestCase
    {
        return new TestCase($this, $route, $data);
    }

    /**
     * Wrap an array in a Collection for fluent manipulation.
     *
     * $app->collect($rows)->pluck('email')->unique()->values()->all();
     */
    public function collect(array $items = []): Collection
    {
        return new Collection($items);
    }

    /**
     * API resource formatting.
     *
     * $app->resource($user)->only('id', 'name', 'email');
     */
    public function resource($data): Resource
    {
        return new Resource($data);
    }

    // ======================== I18N ========================

    /**
     * Translate a key. Loads JSON files from lang/{locale}/.
     *
     * $app->trans('auth.failed', [], 'pt');
     * $app->trans('validation.required', ['field' => 'Email']);
     */
    public function trans(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->config['locale'] ?? 'en';
        $file = $this->paths['root'] . "lang/{$locale}.json";
        $translations = [];

        if (file_exists($file)) {
            $translations = json_decode(file_get_contents($file), true) ?? [];
        }

        // Dot notation: 'auth.failed' → $translations['auth']['failed']
        $value = $translations[$key] ?? $key;

        foreach ($replace as $k => $v) {
            $value = str_replace(':' . $k, $v, $value);
        }

        return $value;
    }

    // ======================== CACHE (REDIS) ========================

    /**
     * Cache with optional Redis driver.
     */
    public function cache(string $key, $value = null, ?int $ttl = null)
    {
        $driver = $this->config['cache']['driver'] ?? 'file';

        if ($driver === 'redis') {
            return $this->cache_redis($key, $value, $ttl);
        }

        // Default: file-based
        $file = $this->storage('cache') . '/' . md5($key) . '.cache';
        if ($value === null) {
            if (!file_exists($file)) return null;
            $data = json_decode(file_get_contents($file), true);
            if (($data['expires'] ?? 0) < time()) { unlink($file); return null; }
            return $data['value'] ?? null;
        }
        $ttl = $ttl ?? $this->config['cache']['ttl'] ?? 3600;
        if (is_dir(dirname($file))) file_put_contents($file, json_encode(['value' => $value, 'expires' => time() + $ttl]), LOCK_EX);
        return $this;
    }

    private $redis = null;

    private function cache_redis(string $key, $value = null, ?int $ttl = null)
    {
        if (!$this->redis) {
            $cfg = $this->config['cache'] ?? [];
            $host = $cfg['host'] ?? '127.0.0.1';
            $port = (int)($cfg['port'] ?? 6379);
            $this->redis = new \Redis();
            $this->redis->connect($host, $port);
            if (!empty($cfg['password'])) $this->redis->auth($cfg['password']);
        }

        if ($value === null) {
            $data = $this->redis->get($key);
            return $data ? json_decode($data, true) : null;
        }

        $ttl = $ttl ?? $this->config['cache']['ttl'] ?? 3600;
        $this->redis->setex($key, $ttl, json_encode($value));
        return $this;
    }

    // ======================== STORAGE (S3) ========================

    /**
     * Cloud storage — S3-compatible. Falls back to local files.
     *
     * $app->store('bucket/file.txt', 'content');
     * $content = $app->store('bucket/file.txt');
     */
    public function store(string $path, ?string $content = null)
    {
        $driver = $this->config['storage']['driver'] ?? 'local';

        if ($content === null) {
            if ($driver === 's3') return $this->store_s3_get($path);
            $full = $this->storage('uploads') . '/' . ltrim($path, '/');
            return file_exists($full) ? file_get_contents($full) : null;
        }

        if ($driver === 's3') return $this->store_s3_put($path, $content);
        $full = $this->storage('uploads') . '/' . ltrim($path, '/');
        $dir = dirname($full);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        return file_put_contents($full, $content);
    }

    private function store_s3_put(string $path, string $content): bool
    {
        $cfg = $this->config['storage'];
        $method = 'PUT';
        $date = gmdate('D, d M Y H:i:s \G\M\T');
        $payload = hash('sha256', $content);
        $region = $cfg['region'] ?? 'us-east-1';
        $service = 's3';
        $host = $cfg['endpoint'] ?? "{$cfg['bucket']}.s3.{$region}.amazonaws.com";

        $headers = [
            'Host: ' . $host,
            'Content-Type: application/octet-stream',
            'x-amz-content-sha256: ' . $payload,
            'x-amz-date: ' . gmdate('Ymd\THis\Z'),
        ];

        $ch = curl_init("https://{$host}/{$path}");
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $content,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $httpCode === 200;
    }

    private function store_s3_get(string $path): ?string
    {
        $cfg = $this->config['storage'];
        $region = $cfg['region'] ?? 'us-east-1';
        $host = $cfg['endpoint'] ?? "{$cfg['bucket']}.s3.{$region}.amazonaws.com";

        $ch = curl_init("https://{$host}/{$path}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $r = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code === 200 ? $r : null;
    }

    /**
     * Public URL for a stored file.
     */
    public function store_url(string $path): string
    {
        $driver = $this->config['storage']['driver'] ?? 'local';
        if ($driver === 's3') {
            $cfg = $this->config['storage'];
            $region = $cfg['region'] ?? 'us-east-1';
            return ($cfg['endpoint'] ?? "https://{$cfg['bucket']}.s3.{$region}.amazonaws.com") . '/' . $path;
        }
        return '/uploads/' . ltrim($path, '/');
    }

    // ======================== AI ========================

    /**
     * AI integration — OpenRouter, OpenAI, or any OpenAI-compatible API.
     *
     * $app->ai()->chat('What is PHP?');
     * $app->ai()->model('openai/gpt-4o')->chat('Explain REST');
     * $app->ai()->system('You are helpful')->chat('Hi');
     */
    public function ai(): AiClient
    {
        return new AiClient($this->config['ai'] ?? []);
    }
}

/**
 * Trindade AI Client — OpenRouter, OpenAI, Claude, any OpenAI-compatible API.
 *
 * $app->ai()->system('You are helpful')->chat('What is PHP?');
 * $app->ai()->model('openai/gpt-4o')->temperature(0.7)->chat($prompt);
 */
class AiClient
{
    private array $config;
    private array $messages = [];
    private string $model;
    private float $temperature = 0.7;
    private int $maxTokens = 1024;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->model = $config['model'] ?? 'openai/gpt-4o-mini';
    }

    public function model(string $model): self { $this->model = $model; return $this; }
    public function temperature(float $t): self { $this->temperature = $t; return $this; }
    public function maxTokens(int $n): self { $this->maxTokens = $n; return $this; }
    public function system(string $prompt): self { $this->messages[] = ['role' => 'system', 'content' => $prompt]; return $this; }

    /**
     * Send a chat message and get the response.
     *
     * $reply = $app->ai()->system('You are a PHP expert')->chat('Write a hello world');
     */
    public function chat(string $prompt): string
    {
        $this->messages[] = ['role' => 'user', 'content' => $prompt];

        $provider = $this->config['provider'] ?? 'openrouter';
        $url = match ($provider) {
            'openai'    => 'https://api.openai.com/v1/chat/completions',
            'claude'    => 'https://api.anthropic.com/v1/messages',
            default     => 'https://openrouter.ai/api/v1/chat/completions',
        };

        $key = $this->config['key'] ?? $this->config['api_key'] ?? '';
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $key,
        ];

        if ($provider === 'openrouter') {
            $headers[] = 'HTTP-Referer: https://trindade.dev';
            $headers[] = 'X-Title: Trindade App';
        }

        $body = json_encode([
            'model'       => $this->model,
            'messages'    => $this->messages,
            'temperature' => $this->temperature,
            'max_tokens'  => $this->maxTokens,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status !== 200) return "AI error: HTTP {$status} — {$response}";

        $data = json_decode($response, true);

        // OpenAI / OpenRouter format
        $content = $data['choices'][0]['message']['content'] ?? null;
        if ($content) {
            $this->messages[] = ['role' => 'assistant', 'content' => $content];
            return $content;
        }

        // Claude format
        $content = $data['content'][0]['text'] ?? null;
        if ($content) {
            $this->messages[] = ['role' => 'assistant', 'content' => $content];
            return $content;
        }

        return "AI response: " . json_encode($data);
    }

    /**
     * Get all messages (for debugging or saving context).
     */
    public function messages(): array { return $this->messages; }
}

/**
 * Trindade TestCase — chainable HTTP assertions.
 */
class TestCase
{
    private Trindade $app;
    private string $route;
    private array $data;
    private int $status = 200;
    private string $body = '';
    private array $headers = [];
    private ?array $json = null;
    private ?string $token = null;

    public function __construct(Trindade $app, string $route, array $data = [])
    {
        $this->app = $app;
        $this->route = $route;
        $this->data = $data;
        $this->run();
    }

    public function withToken(string $token): self { $this->token = $token; return $this->run(); }

    public function withHeaders(array $headers): self { $this->headers = $headers; return $this->run(); }

    private function run(): self
    {
        [$method, $path] = explode(' ', $this->route, 2) + [1 => '/'];

        $_SERVER['REQUEST_METHOD'] = strtoupper($method);
        $_SERVER['REQUEST_URI'] = $path;
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_GET = []; $_POST = $this->data;

        if ($this->token) {
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->token;
        }

        foreach ($this->headers as $k => $v) {
            $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $k))] = $v;
        }

        ob_start();
        $this->app->run();
        $this->body = ob_get_clean();
        $this->status = http_response_code() ?: 200;

        $decoded = json_decode($this->body, true);
        if (json_last_error() === JSON_ERROR_NONE) $this->json = $decoded;

        return $this;
    }

    public function assertOk(): self { return $this->assertStatus(200); }
    public function assertCreated(): self { return $this->assertStatus(201); }
    public function assertNoContent(): self { return $this->assertStatus(204); }
    public function assertUnauthorized(): self { return $this->assertStatus(401); }
    public function assertForbidden(): self { return $this->assertStatus(403); }
    public function assertNotFound(): self { return $this->assertStatus(404); }
    public function assertStatus(int $code): self { assert($this->status === $code, "Expected status {$code}, got {$this->status}"); return $this; }

    public function assertJson(): self { assert($this->json !== null, 'Response is not valid JSON'); return $this; }
    public function assertJsonCount(int $count): self { $this->assertJson(); assert(count($this->json) === $count, "Expected {$count} items, got " . count($this->json)); return $this; }
    public function assertJsonPath(string $path, $expected): self { $this->assertJson(); $val = $this->json; foreach (explode('.', $path) as $k) $val = $val[$k] ?? null; assert($val === $expected, "Expected '{$expected}' at {$path}, got " . json_encode($val)); return $this; }
    public function assertSee(string $text): self { assert(str_contains($this->body, $text), "Text '{$text}' not found in response"); return $this; }
    public function assertHeader(string $key, string $value): self { $headers = xdebug_get_headers(); assert(in_array("{$key}: {$value}", $headers), "Header {$key}: {$value} not found"); return $this; }
}