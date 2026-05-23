<?php
/**
 * Trindade Routes
 */

// ── Middleware (runs before every route) ─────────
$app->use(function ($next) use ($app) {
    $app->log("{$_SERVER['REQUEST_METHOD']} {$_SERVER['REQUEST_URI']}");
    return $next();
});

// ── Public ──────────────────────────────────────
$app->on('GET /', fn() => $app->success(['framework' => 'Trindade']));
$app->on('GET /status', fn() => ['ok' => true, 'php' => PHP_VERSION]);

// ── JWT ─────────────────────────────────────────
$app->on('POST /login', function () use ($app) {
    $body = $app->body();
    // Validate user...
    $token = $app->jwt(['user' => $body['email'] ?? 'guest']);
    return $app->success(['token' => $token]);
});

$app->on('GET /me', function () use ($app) {
    $data = $app->jwt($app->token());
    if (!$data) return $app->error('Invalid token', 401);
    return $app->success($data);
});

// ── CSRF ────────────────────────────────────────
$app->on('GET /csrf', fn() => $app->success(['token' => $app->csrf()]));

// ── QR ──────────────────────────────────────────
$app->on('GET /qr', function () use ($app) {
    $data = $app->request('data', 'get') ?? 'https://trindade.dev';
    return $app->raw('<img src="' . $app->qr($data, (int)($app->request('size', 'get') ?? 200)) . '" alt="QR">');
});

// ── Database + Pagination ───────────────────────
$app->on('GET /users', function () use ($app) {
    if (!$app->db) return $app->error('No database configured');

    $page = (int)($app->request('page', 'get') ?? 1);
    return $app->db->pages('users', 10, $page);
});

// ── Cache ───────────────────────────────────────
$app->on('GET /cache/:key', function () use ($app) {
    $key = $app->param('key');
    $value = $app->cache($key);
    if ($value !== null) return $app->success(['cached' => true, 'value' => $value]);
    $app->cache($key, "Value @ " . date('H:i:s'), 60);
    return $app->success(['cached' => false]);
});

// ── Mail ────────────────────────────────────────
$app->on('POST /mail', function () use ($app) {
    $data = $app->body();
    $ok = $app->mail->to($data['to'])->subject($data['subject'])->message($data['message'])->send();
    return $ok ? $app->success(null, 'Sent') : $app->error($app->mail->errors()[0] ?? 'Failed');
});

// ── SSE (Server-Sent Events) ───────────────────
$app->on('GET /events', function () use ($app) {
    $app->sse(function ($send) use ($app) {
        $i = 0;
        while ($i < 100) {
            $send('ping', ['time' => date('H:i:s'), 'count' => $i]);
            sleep(1);
            $i++;
        }
    });
});
$app->on('POST /upload', function () use ($app) {
    $file = $app->upload('file');
    return $file ? $app->success(['file' => $file]) : $app->error('Upload failed');
});

// ── Docs ────────────────────────────────────────
$app->on('GET /docs', fn() => $app->docs());
