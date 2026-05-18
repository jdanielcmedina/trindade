<?php
namespace Trindade\Plugins;

class Studio
{
    private $app;

    public function __construct($app)
    {
        $this->app = $app;

        // Register studio routes under /studio
        $app->group('/studio', function () use ($app) {
            $app->on('GET /', [$this, 'index']);
            $app->on('GET /login', [$this, 'login']);
            $app->on('POST /login', [$this, 'do_login']);
            $app->on('GET /logout', [$this, 'logout']);

            // API endpoints (protected)
            $app->on('GET /api/stats', [$this, 'api_stats']);
            $app->on('GET /api/routes', [$this, 'api_routes']);
            $app->on('POST /api/routes', [$this, 'api_route_save']);
            $app->on('POST /api/routes/delete', [$this, 'api_route_delete']);
            $app->on('GET /api/db/tables', [$this, 'api_db_tables']);
            $app->on('GET /api/db/table/:table', [$this, 'api_db_table']);
            $app->on('POST /api/db/query', [$this, 'api_db_query']);
            $app->on('GET /api/files', [$this, 'api_files']);
            $app->on('GET /api/file', [$this, 'api_file_get']);
            $app->on('POST /api/file', [$this, 'api_file_save']);
            $app->on('GET /api/logs', [$this, 'api_logs']);
            $app->on('POST /api/request', [$this, 'api_request']);
            $app->on('GET /api/export', [$this, 'api_export']);

            // NIS2
            $app->on('GET /api/nis2', [$this, 'api_nis2']);
            $app->on('GET /api/nis2/totp', [$this, 'api_nis2_totp']);
            $app->on('POST /api/nis2/encrypt', [$this, 'api_nis2_encrypt']);
            $app->on('POST /api/nis2/decrypt', [$this, 'api_nis2_decrypt']);
            $app->on('POST /api/nis2/policy', [$this, 'api_nis2_policy']);
            $app->on('POST /api/nis2/backup', [$this, 'api_nis2_backup']);
            $app->on('GET /api/nis2/audit', [$this, 'api_nis2_audit']);
            $app->on('POST /api/nis2/alert', [$this, 'api_nis2_alert']);
        });
    }

    private function auth(): bool
    {
        $pass = $this->app->config('studio')['password'] ?? 'trindade';
        return $this->app->session('studio_auth') === md5($pass);
    }

    public function login()
    {
        return $this->html_shell('<div class="login-box">
            <h1>Trindade Studio</h1>
            <form method="post" action="/studio/login">
                <input type="password" name="password" placeholder="Password" autofocus>
                <button type="submit">Enter</button>
            </form>
        </div>', false);
    }

    public function do_login()
    {
        $pass = $this->app->config('studio')['password'] ?? 'trindade';
        $input = $this->app->request('password');
        if ($input === $pass) {
            $this->app->session('studio_auth', md5($pass));
            $this->app->redirect('/studio');
        }
        $this->app->redirect('/studio/login');
    }

    public function logout()
    {
        $this->app->session('studio_auth', false);
        $this->app->redirect('/studio/login');
    }

    public function index()
    {
        if (!$this->auth()) return $this->app->redirect('/studio/login');
        return $this->html();
    }

    private function html(): string
    {
        return $this->html_shell('
<div id="app" class="app">
    <nav class="nav">
        <div class="nav-brand">⚡ Trindade Studio</div>
        <a class="nav-item active" data-page="dashboard" onclick="nav(this,\'dashboard\')">📊 Dashboard</a>
        <a class="nav-item" data-page="routes" onclick="nav(this,\'routes\')">🔀 Routes</a>
        <a class="nav-item" data-page="database" onclick="nav(this,\'database\')">🗄 Database</a>
        <a class="nav-item" data-page="files" onclick="nav(this,\'files\')">📁 Files</a>
        <a class="nav-item" data-page="console" onclick="nav(this,\'console\')">📡 API Console</a>
        <a class="nav-item" data-page="logs" onclick="nav(this,\'logs\')">📋 Logs</a>
        <a class="nav-item" data-page="nis2" onclick="nav(this,\'nis2\')">🛡 NIS2</a>
        <div class="nav-footer"><a href="/studio/logout" style="color:#999">Logout</a></div>
    </nav>
    <main id="main"></main>
</div>
<script>
let currentPage = "dashboard";
const api = (url, opts = {}) => fetch("/studio/api" + url, {headers:{"Content-Type":"application/json"},...opts}).then(r => r.json());

function nav(el, page) {
    document.querySelectorAll(".nav-item").forEach(n => n.classList.remove("active"));
    el.classList.add("active");
    currentPage = page;
    load(page);
}

async function load(page) {
    const m = document.getElementById("main");
    m.innerHTML = "<p>Loading...</p>";
    switch(page) {
        case "dashboard": m.innerHTML = await dashboard(); break;
        case "routes": m.innerHTML = await routes_page(); break;
        case "database": m.innerHTML = await database_page(); break;
        case "files": m.innerHTML = await files_page(); break;
        case "console": m.innerHTML = console_page(); break;
        case "logs": m.innerHTML = await logs_page(); break;
        case "nis2": m.innerHTML = await nis2_page(); break;
    }
}

async function dashboard() {
    const s = await api("/stats");
    return `<h2>Dashboard</h2>
    <div class="cards">
        <div class="card"><h3>PHP</h3><p>${s.php}</p></div>
        <div class="card"><h3>Routes</h3><p>${s.routes} routes</p></div>
        <div class="card"><h3>DB Tables</h3><p>${s.tables}</p></div>
        <div class="card"><h3>Storage</h3><p>${s.storage}</p></div>
    </div>`;
}

async function routes_page() {
    const r = await api("/routes");
    let h = `<h2>Routes <button class="btn" onclick="add_route()">+ New</button></h2>
    <div id="route-list">`;
    Object.entries(r).forEach(([method, routes]) => {
        Object.entries(routes).forEach(([path, info]) => {
            const vhost = info.vhost ? ` <small>[${info.vhost}]</small>` : "";
            h += `<div class="route-row"><span class="method ${method.toLowerCase()}">${method}</span> <b>${path}</b>${vhost}
            <button class="btn-sm" onclick="del_route(\'${method}\',\'${path}\')">🗑</button></div>`;
        });
    });
    h += `</div><div id="route-edit" style="display:none;margin-top:1rem"></div>`;
    return h;
}

async function add_route() {
    const ed = document.getElementById("route-edit");
    ed.style.display = "block";
    ed.innerHTML = `<h3>New Route</h3>
    <select id="re-method"><option>GET</option><option>POST</option><option>PUT</option><option>DELETE</option><option>PATCH</option><option>GET|POST</option></select>
    <input id="re-path" placeholder="/example/:id">
    <textarea id="re-code" rows="10" placeholder="return $app->success([\'id\' => $app->param(\'id\')]);"></textarea>
    <button class="btn" onclick="save_route()">Save</button>
    <button class="btn" onclick="document.getElementById(\'route-edit\').style.display=\'none\'">Cancel</button>`;
}

async function save_route() {
    const method = document.getElementById("re-method").value;
    const path = document.getElementById("re-path").value;
    const code = document.getElementById("re-code").value;
    await api("/routes", {method:"POST", body:JSON.stringify({method,path,code})});
    load("routes");
}

async function del_route(method, path) {
    if (!confirm(`Delete ${method} ${path}?`)) return;
    await api("/routes/delete", {method:"POST", body:JSON.stringify({method,path})});
    load("routes");
}

async function database_page() {
    let tables;
    try { tables = await api("/db/tables"); } catch(e) { return "<h2>Database</h2><p>No database configured.</p>"; }
    let h = `<h2>Database</h2><div class="db-tables">`;
    tables.forEach(t => { h += `<button class="btn table-btn" onclick="browse_table(\'${t}\')">📋 ${t}</button>`; });
    h += `</div>
    <h3>SQL Console</h3>
    <textarea id="sql-query" rows="4" placeholder="SELECT * FROM users LIMIT 10"></textarea>
    <button class="btn" onclick="run_query()">Run</button>
    <div id="query-result" class="result-table" style="margin-top:1rem"></div>
    <div id="table-browse" style="margin-top:1rem"></div>`;
    return h;
}

async function browse_table(table) {
    const data = await api("/db/table/" + table);
    let h = `<h3>📋 ${table} (${data.length} rows)</h3><div class="result-table"><table><tr>`;
    if (data.length > 0) {
        Object.keys(data[0]).forEach(k => h += `<th>${k}</th>`);
        h += "</tr>";
        data.forEach(row => {
            h += "<tr>";
            Object.values(row).forEach(v => h += `<td>${v !== null ? v : "<i>null</i>"}</td>`);
            h += "</tr>";
        });
    }
    h += "</table></div>";
    document.getElementById("table-browse").innerHTML = h;
}

async function run_query() {
    const sql = document.getElementById("sql-query").value;
    const result = await api("/db/query", {method:"POST", body:JSON.stringify({sql})});
    const div = document.getElementById("query-result");
    if (result.error) { div.innerHTML = `<p class="error">${result.error}</p>`; return; }
    if (!result.rows || result.rows.length === 0) { div.innerHTML = "<p>No results.</p>"; return; }
    let h = `<table><tr>`;
    Object.keys(result.rows[0]).forEach(k => h += `<th>${k}</th>`);
    h += "</tr>";
    result.rows.forEach(row => { h += "<tr>"; Object.values(row).forEach(v => h += `<td>${v !== null ? v : "<i>null</i>"}</td>`); h += "</tr>"; });
    h += "</table>";
    div.innerHTML = h;
}

async function files_page() {
    const files = await api("/files");
    let h = `<h2>Files</h2><div class="file-tree">`;
    Object.entries(files).forEach(([dir, items]) => {
        h += `<h3>📂 ${dir}/</h3>`;
        items.forEach(f => { h += `<div class="file-item" onclick="open_file(\'${dir}\',\'${f}\')">📄 ${f}</div>`; });
    });
    h += `</div><div id="file-editor" style="margin-top:1rem"></div>`;
    return h;
}

async function open_file(dir, name) {
    const data = await api("/file?dir=" + dir + "&name=" + name);
    document.getElementById("file-editor").innerHTML = `
    <h3>Editing: ${dir}/${name}</h3>
    <textarea id="file-content" rows="20" style="width:100%;font-family:monospace;background:#1e1e1e;color:#d4d4d4;padding:1rem">${escape_html(data.content || "")}</textarea>
    <button class="btn" onclick="save_file(\'${dir}\',\'${name}\')">💾 Save</button>`;
}

async function save_file(dir, name) {
    const content = document.getElementById("file-content").value;
    await api("/file", {method:"POST", body:JSON.stringify({dir,name,content})});
    alert("Saved!");
}

function console_page() {
    return `<h2>API Console</h2>
    <select id="con-method"><option>GET</option><option>POST</option><option>PUT</option><option>DELETE</option><option>PATCH</option></select>
    <input id="con-url" placeholder="/api/v1/endpoint" style="width:60%">
    <textarea id="con-headers" rows="3" placeholder=\'{"Content-Type":"application/json"}\'></textarea>
    <textarea id="con-body" rows="5" placeholder=\'{"key":"value"}\'></textarea>
    <button class="btn" onclick="send_request()">Send</button>
    <div id="con-result" style="margin-top:1rem"></div>`;
}

async function send_request() {
    const method = document.getElementById("con-method").value;
    const url = document.getElementById("con-url").value;
    const headers = JSON.parse(document.getElementById("con-headers").value || "{}");
    const body = document.getElementById("con-body").value;
    const result = await api("/request", {method:"POST", body:JSON.stringify({method,url,headers,body})});
    document.getElementById("con-result").innerHTML = `<h3>Response</h3>
    <pre>${JSON.stringify(result, null, 2)}</pre>`;
}

async function logs_page() {
    const logs = await api("/logs");
    return `<h2>Logs</h2><pre class="log-viewer">${escape_html(logs)}</pre>`;
}

async function nis2_page() {
    const stats = await api("/nis2");
    return `<h2>🛡 NIS2 Compliance</h2>
    <div class="cards" style="margin-bottom:1rem">
        <div class="card"><h3>Audit Events</h3><p>${stats.audit_count}</p></div>
        <div class="card"><h3>Lockouts</h3><p>${stats.lockouts}</p></div>
        <div class="card"><h3>Backups</h3><p>${stats.backups}</p></div>
        <div class="card"><h3>Alerts</h3><p>${stats.alerts}</p></div>
    </div>
    <div class="nis2-grid">
        <div class="nis2-panel">
            <h3>🔐 TOTP / 2FA</h3>
            <button class="btn" onclick="totp_gen()">Generate Secret</button>
            <div id="totp-result" style="margin-top:.5rem"></div>
        </div>
        <div class="nis2-panel">
            <h3>🔒 Encrypt / Decrypt</h3>
            <textarea id="enc-input" rows="3" placeholder="Data to encrypt or decrypt..."></textarea>
            <button class="btn" onclick="encrypt_data()">Encrypt</button>
            <button class="btn" onclick="decrypt_data()">Decrypt</button>
            <div id="enc-result" style="margin-top:.5rem;word-break:break-all"></div>
        </div>
        <div class="nis2-panel">
            <h3>🔑 Password Policy</h3>
            <input id="pwd-test" placeholder="Test a password..." onkeyup="test_pwd()">
            <div id="pwd-result" style="margin-top:.5rem"></div>
        </div>
        <div class="nis2-panel">
            <h3>📦 Backup</h3>
            <select id="backup-type"><option value="full">Full</option><option value="db">Database only</option><option value="files">Files only</option></select>
            <button class="btn" onclick="run_backup()">Create Backup</button>
            <div id="backup-result" style="margin-top:.5rem"></div>
        </div>
        <div class="nis2-panel">
            <h3>📝 Audit Trail</h3>
            <pre class="log-viewer" style="max-height:300px" id="audit-log">Loading...</pre>
            <button class="btn" onclick="load_audit()">Refresh</button>
        </div>
        <div class="nis2-panel">
            <h3>🚨 Alert Test</h3>
            <select id="alert-level"><option>info</option><option>warning</option><option>critical</option></select>
            <input id="alert-msg" placeholder="Alert message...">
            <button class="btn" onclick="send_alert()">Send Alert</button>
            <div id="alert-result" style="margin-top:.5rem"></div>
        </div>
    </div>`;
}

async function totp_gen() {
    const r = await api("/nis2/totp");
    document.getElementById("totp-result").innerHTML = `
        <p><b>Secret:</b> <code>${r.secret}</code></p>
        <p><b>Current code:</b> <code style="font-size:20px">${r.code}</code></p>
        <p style="color:#888;font-size:12px">Use this secret in Google Authenticator. Code refreshes every 30s.</p>`;
}

async function encrypt_data() {
    const v = document.getElementById("enc-input").value;
    if (!v) return;
    const r = await api("/nis2/encrypt", {method:"POST", body:JSON.stringify({data:v})});
    document.getElementById("enc-result").innerHTML = `<code>${escape_html(r.result)}</code>`;
}

async function decrypt_data() {
    const v = document.getElementById("enc-input").value;
    if (!v) return;
    const r = await api("/nis2/decrypt", {method:"POST", body:JSON.stringify({data:v})});
    document.getElementById("enc-result").innerHTML = `<code>${escape_html(r.result)}</code>`;
}

async function test_pwd() {
    const v = document.getElementById("pwd-test").value;
    const r = await api("/nis2/policy", {method:"POST", body:JSON.stringify({password:v})});
    const d = document.getElementById("pwd-result");
    if (r.valid) d.innerHTML = \'<span style="color:#49cc90">✔ Password meets policy</span>\';
    else d.innerHTML = r.errors.map(e => `<div style="color:#f93e3e">✘ ${e}</div>`).join("");
}

async function run_backup() {
    document.getElementById("backup-result").innerHTML = "Running...";
    const t = document.getElementById("backup-type").value;
    const r = await api("/nis2/backup", {method:"POST", body:JSON.stringify({type:t})});
    document.getElementById("backup-result").innerHTML = r.ok
        ? `<span style="color:#49cc90">✔ Backup created: ${r.file}</span>`
        : `<span style="color:#f93e3e">✘ Failed</span>`;
}

async function load_audit() {
    const r = await api("/nis2/audit");
    document.getElementById("audit-log").innerHTML = r.entries.map(e =>
        `<div>[${e.ts}] <b>${e.action}</b> ${e.ip} ${e.user||""}</div>`
    ).join("\\n") || "No audit events yet.";
    load_audit.ts = Date.now();
}

async function send_alert() {
    const level = document.getElementById("alert-level").value;
    const msg = document.getElementById("alert-msg").value;
    const r = await api("/nis2/alert", {method:"POST", body:JSON.stringify({level,msg})});
    document.getElementById("alert-result").innerHTML = r.ok
        ? "\x3Cspan style=\x22color:#49cc90\x22\x3E✔ Alert sent\x3C/span\x3E"
        : "\x3Cspan style=\x22color:#f93e3e\x22\x3E✘ Failed\x3C/span\x3E";
}

function escape_html(s) { return s.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;"); }

load("dashboard");
load_audit();
</script>');
    }

    private function html_shell(string $body, bool $full = true): string
    {
        $nav = $full ? '' : '';
        $style = '
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:-apple-system,system-ui,sans-serif;background:#0f0f0f;color:#e0e0e0;display:flex;min-height:100vh}
        .app{display:flex;width:100%}
        .nav{width:220px;background:#1a1a1a;padding:1rem;display:flex;flex-direction:column;gap:4px;border-right:1px solid #333}
        .nav-brand{font-size:18px;font-weight:700;padding:.5rem 0 1rem;color:#6c5ce7}
        .nav-item{padding:.6rem .8rem;border-radius:6px;cursor:pointer;color:#aaa;text-decoration:none;font-size:14px;transition:.15s}
        .nav-item:hover,.nav-item.active{background:#2a2a2a;color:#fff}
        .nav-footer{margin-top:auto;padding-top:1rem;font-size:13px}
        main{flex:1;padding:2rem;overflow-y:auto}
        h2{font-size:20px;margin-bottom:1rem;color:#fff}
        h3{font-size:16px;margin:1rem 0 .5rem;color:#ccc}
        .cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem}
        .card{background:#1a1a1a;padding:1.2rem;border-radius:8px;border:1px solid #333}
        .card h3{font-size:13px;color:#888;margin:0 0 .5rem;text-transform:uppercase;letter-spacing:.5px}
        .card p{font-size:24px;font-weight:600;color:#fff}
        .btn{background:#6c5ce7;color:#fff;border:none;padding:.5rem 1rem;border-radius:6px;cursor:pointer;font-size:14px;margin:.25rem}
        .btn:hover{background:#7c6cf7}
        .btn-sm{background:#333;color:#aaa;border:none;padding:.2rem .5rem;border-radius:4px;cursor:pointer;font-size:12px;margin-left:.5rem}
        input,select,textarea{background:#1a1a1a;border:1px solid #333;color:#e0e0e0;padding:.5rem;border-radius:6px;font-size:14px;margin:.25rem 0;width:100%}
        textarea{resize:vertical;font-family:monospace}
        table{width:100%;border-collapse:collapse;margin:.5rem 0}
        td,th{padding:.4rem .6rem;border:1px solid #333;font-size:13px;text-align:left}
        th{background:#1a1a1a;color:#888;font-weight:600}
        .method{display:inline-block;padding:2px 6px;border-radius:3px;font-size:11px;font-weight:700;margin-right:.5rem;min-width:44px;text-align:center}
        .method.get{background:#61affe;color:#fff}.method.post{background:#49cc90;color:#fff}
        .method.put{background:#fca130;color:#fff}.method.delete{background:#f93e3e;color:#fff}
        .method.patch{background:#50e3c2;color:#111}
        .route-row{padding:.4rem 0;border-bottom:1px solid #222;display:flex;align-items:center}
        .db-tables{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem}
        .table-btn{background:#2a2a2a}
        .file-tree{background:#1a1a1a;padding:1rem;border-radius:8px}
        .file-item{padding:.3rem .5rem;cursor:pointer;border-radius:4px}
        .file-item:hover{background:#2a2a2a}
        .result-table{overflow-x:auto;max-height:400px;overflow-y:auto}
        .log-viewer{background:#1a1a1a;padding:1rem;border-radius:8px;font-size:12px;max-height:500px;overflow-y:auto;white-space:pre-wrap;color:#aaa}
        .login-box{max-width:360px;margin:100px auto;background:#1a1a1a;padding:2rem;border-radius:12px;text-align:center;border:1px solid #333}
        .login-box h1{color:#6c5ce7;margin-bottom:1rem}
        .login-box input{margin:.5rem 0}
        .login-box button{width:100%;margin-top:.5rem}
        .error{color:#f93e3e}
        .nis2-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1rem}
        .nis2-panel{background:#1a1a1a;padding:1rem;border-radius:8px;border:1px solid #333}
        ';

        $head = $full ? '<a href="/studio/logout" style="position:fixed;top:1rem;right:1rem;color:#666;font-size:13px;z-index:99">Logout</a>' : '';

        return '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Trindade Studio</title><style>' . $style . '</style></head><body>' . $head . $body . '</body></html>';
    }

    // ======================== API ENDPOINTS ========================

    private function check(): void
    {
        if (!$this->auth()) { echo json_encode(['error' => 'Unauthorized']); exit; }
    }

    public function api_stats()
    {
        $this->check();
        $routes = 0;
        foreach ($this->app->routes() as $m => $rs) $routes += count($rs);
        $tables = 0;
        if ($this->app->db) {
            try { $t = $this->app->db->query("SHOW TABLES")->fetchAll(); $tables = count($t); } catch (\Throwable $e) {}
        }
        $storage = $this->app->storage();
        $size = is_dir($storage) ? $this->dir_size($storage) : 0;
        echo json_encode([
            'php'     => PHP_VERSION,
            'routes'  => $routes,
            'tables'  => $tables,
            'storage' => $this->fmt_size($size),
        ]);
    }

    public function api_routes()
    {
        $this->check();
        echo json_encode($this->app->routes());
    }

    public function api_route_save()
    {
        $this->check();
        $data = json_decode(file_get_contents('php://input'), true);
        $method = $data['method'] ?? 'GET';
        $path = $data['path'] ?? '/';
        $code = $data['code'] ?? '';

        $file = $this->app->path('routes') . '/web.php';
        $content = file_get_contents($file);
        $line = "\$app->on('{$method} {$path}', function () use (\$app) {\n    {$code}\n});\n";
        file_put_contents($file, $content . "\n" . $line);
        echo json_encode(['ok' => true]);
    }

    public function api_route_delete()
    {
        $this->check();
        $data = json_decode(file_get_contents('php://input'), true);
        $method = $data['method'] ?? '';
        $path = $data['path'] ?? '';
        $file = $this->app->path('routes') . '/web.php';
        $content = file_get_contents($file);

        $esc_method = preg_quote($method, '/');
        $esc_path = preg_quote($path, '/');
        $pattern = "/\\\$app->on\\('{$esc_method}\\s+{$esc_path}',\\s*function\\s*\\([^)]*\\)\\s*use\\s*\\([^)]*\\)\\s*\\{[^}]*\\}\\);/s";

        $content = preg_replace($pattern, '', $content);
        file_put_contents($file, $content);
        echo json_encode(['ok' => true]);
    }

    public function api_db_tables()
    {
        $this->check();
        if (!$this->app->db) { echo json_encode([]); return; }
        try {
            $type = $this->app->db->type();
            $sql = $type === 'sqlite' ? "SELECT name FROM sqlite_master WHERE type='table'" : "SHOW TABLES";
            $rows = $this->app->db->query($sql)->fetchAll(\PDO::FETCH_NUM);
            echo json_encode(array_map(fn($r) => $r[0], $rows));
        } catch (\Throwable $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function api_db_table()
    {
        $this->check();
        if (!$this->app->db) { echo json_encode([]); return; }
        $table = $this->app->param('table');
        try {
            $rows = $this->app->db->query("SELECT * FROM {$table} LIMIT 100")->fetchAll();
            echo json_encode($rows);
        } catch (\Throwable $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function api_db_query()
    {
        $this->check();
        if (!$this->app->db) { echo json_encode(['error' => 'No database']); return; }
        $data = json_decode(file_get_contents('php://input'), true);
        $sql = $data['sql'] ?? '';
        try {
            $stmt = $this->app->db->query($sql);
            if (preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN)/i', $sql)) {
                echo json_encode(['rows' => $stmt->fetchAll()]);
            } else {
                echo json_encode(['rows' => [], 'affected' => $stmt->rowCount()]);
            }
        } catch (\Throwable $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function api_files()
    {
        $this->check();
        $dirs = ['routes', 'helpers', 'views'];
        $result = [];
        foreach ($dirs as $d) {
            $p = $this->app->path($d);
            if ($p && is_dir($p)) {
                $files = [];
                foreach (scandir($p) as $f) {
                    if ($f !== '.' && $f !== '..' && $f !== '.gitkeep') $files[] = $f;
                }
                if (!empty($files)) $result[$d] = $files;
            }
        }
        echo json_encode($result);
    }

    public function api_file_get()
    {
        $this->check();
        $dir = $_GET['dir'] ?? '';
        $name = $_GET['name'] ?? '';
        $allowed = ['routes', 'helpers', 'views'];
        if (!in_array($dir, $allowed)) { echo json_encode(['error' => 'Invalid dir']); return; }
        $path = $this->app->path($dir) . '/' . basename($name);
        if (!file_exists($path)) { echo json_encode(['content' => '']); return; }
        echo json_encode(['content' => file_get_contents($path)]);
    }

    public function api_file_save()
    {
        $this->check();
        $data = json_decode(file_get_contents('php://input'), true);
        $dir = $data['dir'] ?? '';
        $name = $data['name'] ?? '';
        $content = $data['content'] ?? '';
        $allowed = ['routes', 'helpers', 'views'];
        if (!in_array($dir, $allowed)) { echo json_encode(['error' => 'Invalid dir']); return; }
        $path = $this->app->path($dir) . '/' . basename($name);
        file_put_contents($path, $content);
        echo json_encode(['ok' => true]);
    }

    public function api_logs()
    {
        $this->check();
        $log = $this->app->storage('logs') . '/app.log';
        if (file_exists($log)) {
            $content = file_get_contents($log);
            $lines = array_slice(explode("\n", $content), -100);
            echo json_encode(implode("\n", $lines));
        } else {
            echo json_encode('No logs yet.');
        }
    }

    public function api_request()
    {
        $this->check();
        $data = json_decode(file_get_contents('php://input'), true);
        $method = $data['method'] ?? 'GET';
        $url = $data['url'] ?? '';
        $headers = $data['headers'] ?? [];
        $body = $data['body'] ?? '';

        // Local request to own routes
        $parts = parse_url($url);
        $path = $parts['path'] ?? '/';
        parse_str($parts['query'] ?? '', $query);
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $path . (!empty($query) ? '?' . http_build_query($query) : '');
        $_GET = $query;
        if ($body) $_POST = is_string($body) ? json_decode($body, true) ?? [] : $body;

        ob_start();
        $this->app->run();
        $response = ob_get_clean();

        echo json_encode([
            'status' => http_response_code(),
            'body'   => $response,
        ]);
    }

    public function api_export()
    {
        $this->check();
        $routes = $this->app->routes();
        $export = "<?php\n// Trindade Studio Export\n";
        foreach ($routes as $method => $rs) {
            foreach ($rs as $path => $info) {
                $export .= "\$app->on('{$method} {$path}', function () use (\$app) {\n    // TODO\n});\n";
            }
        }
        header('Content-Disposition: attachment; filename="routes_export.php"');
        header('Content-Type: text/plain');
        echo $export;
    }

    public function api_nis2()
    {
        $this->check();
        $audit = $this->app->audit_log(1000);
        $cache = $this->app->storage('cache');
        $lockouts = 0;
        foreach (glob($cache . '/lockout_*.cache') as $f) $lockouts++;
        $backups = count(glob(($this->app->storage('backups') ?: $this->app->storage('cache')) . '/backup-*.zip'));
        echo json_encode([
            'audit_count' => count($audit),
            'lockouts'    => $lockouts,
            'backups'     => $backups,
            'alerts'      => count(array_filter($audit, fn($e) => str_starts_with($e['action'] ?? '', 'alert.'))),
        ]);
    }

    public function api_nis2_totp()
    {
        $this->check();
        $secret = $this->app->totp();
        $code = $this->app->totp($secret);
        echo json_encode(['secret' => $secret, 'code' => $code]);
    }

    public function api_nis2_encrypt()
    {
        $this->check();
        $data = json_decode(file_get_contents('php://input'), true);
        $result = $this->app->encrypt($data['data'] ?? '');
        echo json_encode(['result' => $result]);
    }

    public function api_nis2_decrypt()
    {
        $this->check();
        $data = json_decode(file_get_contents('php://input'), true);
        $result = $this->app->decrypt($data['data'] ?? '');
        echo json_encode(['result' => $result ?? 'Decryption failed']);
    }

    public function api_nis2_policy()
    {
        $this->check();
        $data = json_decode(file_get_contents('php://input'), true);
        $errors = $this->app->password_policy($data['password'] ?? '');
        echo json_encode(['valid' => empty($errors), 'errors' => $errors]);
    }

    public function api_nis2_backup()
    {
        $this->check();
        $data = json_decode(file_get_contents('php://input'), true);
        $type = $data['type'] ?? 'full';
        $file = $this->app->backup($type);
        echo json_encode(['ok' => $file !== false, 'file' => $file ? basename($file) : '']);
    }

    public function api_nis2_audit()
    {
        $this->check();
        $entries = $this->app->audit_log();
        echo json_encode(['entries' => $entries]);
    }

    public function api_nis2_alert()
    {
        $this->check();
        $data = json_decode(file_get_contents('php://input'), true);
        $this->app->alert($data['level'] ?? 'info', $data['msg'] ?? 'Studio test alert');
        echo json_encode(['ok' => true]);
    }

    private function dir_size(string $dir): int
    {
        $size = 0;
        foreach (glob(rtrim($dir, '/') . '/*', GLOB_NOSORT) as $f) {
            $size += is_file($f) ? filesize($f) : $this->dir_size($f);
        }
        return $size;
    }

    private function fmt_size(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}
