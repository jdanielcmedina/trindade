<?php
namespace Trindade\Plugins;

class Monitor
{
    private $app;

    public function __construct($app)
    {
        $this->app = $app;
        $app->group('/monitor', function () use ($app) {
            $app->on('GET /', [$this, 'index']);
            $app->on('GET /stream', [$this, 'stream']);
            $app->on('GET /api/stats', [$this, 'api_stats']);
            $app->on('GET /api/logs', [$this, 'api_logs']);
            $app->on('GET /api/db', [$this, 'api_db']);
            $app->on('GET /api/queue', [$this, 'api_queue']);
            $app->on('GET /api/requests', [$this, 'api_requests']);
            $app->notfound([$this, 'index']);
        });
    }

    public function index()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Serve static assets from monitor-ui/dist/
        if (str_starts_with($uri, '/monitor/assets/')) {
            $file = $this->app->path('root') . 'monitor-ui/dist' . substr($uri, 8);
            if (file_exists($file) && !is_dir($file)) {
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                $mimes = ['js' => 'application/javascript', 'css' => 'text/css', 'svg' => 'image/svg+xml'];
                header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
                header('Cache-Control: public, max-age=31536000');
                readfile($file);
                exit;
            }
        }

        // Serve React build
        $dist = $this->app->path('root') . 'monitor-ui/dist/index.html';
        if (file_exists($dist)) {
            echo file_get_contents($dist);
            return;
        }
        $this->render();
    }

    public function stream()
    {
        $this->app->sse(function ($send) {
            $start = microtime(true);
            while (true) {
                $send('stats', $this->collect_stats());
                usleep(2000000); // 2s
            }
        });
    }

    private function collect_stats(): array
    {
        $routes = 0; foreach ($this->app->routes() as $rs) $routes += count($rs);
        $queue = $this->app->queue_status();

        $dbs = [];
        foreach ($this->app->db_connections() as $name) {
            $db = $this->app->db($name);
            try { $db->query("SELECT 1")->fetch(); $dbs[$name] = 'connected'; }
            catch (\Throwable $e) { $dbs[$name] = 'error'; }
        }

        $logFile = $this->app->storage('logs') . '/app.log';
        $errors = 0; $warnings = 0;
        if (file_exists($logFile)) {
            $lines = array_slice(file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -100);
            $errors = count(array_filter($lines, fn($l) => stripos($l, '[error]') !== false));
            $warnings = count(array_filter($lines, fn($l) => stripos($l, '[warning]') !== false));
        }

        return [
            'ts'       => date('H:i:s'),
            'php'      => PHP_VERSION,
            'memory'   => round(memory_get_usage(true) / 1048576, 1),
            'routes'   => $routes,
            'dbs'      => $dbs,
            'queue'    => $queue,
            'errors'   => $errors,
            'warnings' => $warnings,
        ];
    }

    public function api_stats() { echo json_encode($this->collect_stats()); }
    public function api_logs() { $log = @file_get_contents($this->app->storage('logs') . '/app.log'); echo json_encode($log ?: ''); }
    public function api_db() { echo json_encode($this->app->db_connections()); }
    public function api_queue() { echo json_encode($this->app->queue_status()); }
    public function api_requests() { echo json_encode(array_slice($this->app->audit_log(20), 0, 20)); }

    private function render()
    {
        echo '<!DOCTYPE html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Monitor — Trindade</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:system-ui,-apple-system,sans-serif;background:#09090b;color:#fafafa}
  .card{background:#18181b;border:1px solid #27272a;border-radius:12px;padding:1.25rem}
  .stat-value{font-size:1.75rem;font-weight:700;letter-spacing:-.5px}
  .stat-label{font-size:.7rem;text-transform:uppercase;letter-spacing:1px;color:#a1a1aa;margin-top:.25rem}
  .badge{display:inline-flex;align-items:center;gap:.35rem;font-size:.7rem;font-weight:600;padding:.2rem .6rem;border-radius:9999px}
  .badge-ok{background:#22c55e15;color:#22c55e}
  .badge-warn{background:#f59e0b15;color:#f59e0b}
  .badge-err{background:#ef444415;color:#ef4444}
  .dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}
  @keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
  .live-dot{animation:pulse 2s infinite}
  td,th{padding:.5rem .75rem;text-align:left;font-size:.8rem}
  th{color:#a1a1aa;font-weight:500;font-size:.7rem;text-transform:uppercase;letter-spacing:.5px}
  pre.logs{font-family:"JetBrains Mono",monospace;font-size:.75rem;color:#a1a1aa;max-height:50vh;overflow-y:auto;white-space:pre-wrap;line-height:1.5}
</style>
</head><body class="min-h-screen p-6 lg:p-10">
<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-lg font-semibold tracking-tight flex items-center gap-3">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Trindade Monitor
            </h1>
            <p class="text-sm text-zinc-500 mt-1">Real-time observability dashboard</p>
        </div>
        <div class="flex items-center gap-2 text-xs text-zinc-500">
            <span class="dot live-dot bg-emerald-400"></span> Live
        </div>
    </div>

    <div id="stats-grid" class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6"></div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="card">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold">Logs</h3>
                <span class="text-xs text-zinc-500" id="log-count">0 lines</span>
            </div>
            <pre id="logs" class="logs">Loading...</pre>
        </div>
        <div class="card">
            <h3 class="text-sm font-semibold mb-3">Recent Requests</h3>
            <div id="requests" class="text-xs text-zinc-500">Loading...</div>
        </div>
    </div>
</div>

<script>
const g = id => document.getElementById(id);

async function load() {
    // Stats
    const s = await fetch("/monitor/api/stats").then(r => r.json());
    let cards = "";
    cards += stat("PHP", s.php, "indigo");
    cards += stat("Memory", s.memory + " MB", "emerald");
    cards += stat("Routes", s.routes, "blue");
    cards += stat("DB", Object.values(s.dbs||{}).join(", ") || "none", "amber");
    cards += stat("Queue", (s.queue?.pending||0) + " pending", s.queue?.pending ? "amber" : "emerald");
    cards += stat("Errors", s.errors, s.errors ? "red" : "emerald");
    cards += stat("Warnings", s.warnings, s.warnings ? "amber" : "emerald");
    cards += stat("Updated", s.ts, "zinc");
    g("stats-grid").innerHTML = cards;

    // Logs
    const logs = await fetch("/monitor/api/logs").then(r => r.json());
    const lines = (logs || "").split("\n").filter(Boolean).slice(-50);
    g("logs").innerHTML = lines.map(l => {
        if (l.includes("[error]")) return `<span class="text-red-400">${esc(l)}</span>`;
        if (l.includes("[warning]")) return `<span class="text-amber-400">${esc(l)}</span>`;
        return `<span class="text-zinc-500">${esc(l)}</span>`;
    }).join("\n");
    g("log-count").textContent = lines.length + " lines";

    // Requests
    const reqs = await fetch("/monitor/api/requests").then(r => r.json());
    g("requests").innerHTML = reqs.length ? `<table class="w-full">${reqs.map(r => `<tr class="border-b border-zinc-800"><td class="text-zinc-500 font-mono">${(r.ts||"").substring(11,19)}</td><td class="text-indigo-400">${esc(r.action||"")}</td><td class="text-zinc-600">${esc(r.ip||"")}</td></tr>`).join("")}</table>` : "No requests yet.";
}

function stat(label, value, color) {
    const c = {indigo:"#818cf8",emerald:"#34d399",blue:"#60a5fa",amber:"#fbbf24",red:"#f87171",zinc:"#a1a1aa"};
    return `<div class="card"><div class="stat-value" style="color:${c[color]||c.zinc}">${value}</div><div class="stat-label">${label}</div></div>`;
}

function esc(s) { return s.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;"); }

load();
setInterval(load, 3000);

// SSE streaming
const evt = new EventSource("/monitor/stream");
evt.addEventListener("stats", e => {
    const s = JSON.parse(e.data);
    // Pulse the live dot
});
</script>
</body></html>';
    }
}
