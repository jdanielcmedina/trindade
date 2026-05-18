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
    <button class="menu-toggle" onclick="toggle_menu()" aria-label="Menu">
        <span class="bar"></span><span class="bar"></span><span class="bar"></span>
    </button>
    <nav class="nav" id="nav">
        <div class="nav-brand">Trindade Studio</div>
        <a class="nav-item active" data-page="dashboard" onclick="nav(this,\'dashboard\')">Dashboard</a>
        <a class="nav-item" data-page="routes" onclick="nav(this,\'routes\')">Rotas</a>
        <a class="nav-item" data-page="database" onclick="nav(this,\'database\')">Base de Dados</a>
        <a class="nav-item" data-page="files" onclick="nav(this,\'files\')">Ficheiros</a>
        <a class="nav-item" data-page="console" onclick="nav(this,\'console\')">Consola API</a>
        <a class="nav-item" data-page="seguranca" onclick="nav(this,\'seguranca\')">Seguranca</a>
        <a class="nav-item" data-page="auditoria" onclick="nav(this,\'auditoria\')">Auditoria</a>
        <a class="nav-item" data-page="logs" onclick="nav(this,\'logs\')">Logs</a>
        <div class="nav-footer"><a href="/studio/logout" class="logout-link">Terminar sessao</a></div>
    </nav>
    <div class="overlay" id="overlay" onclick="toggle_menu()"></div>
    <main id="main"></main>
</div>
<script>
let currentPage = "dashboard";
const api = (url, opts = {}) => fetch("/studio/api" + url, {headers:{"Content-Type":"application/json"},...opts}).then(r => r.json());

function toggle_menu() {
    document.getElementById("nav").classList.toggle("open");
    document.getElementById("overlay").classList.toggle("show");
}

function nav(el, page) {
    document.querySelectorAll(".nav-item").forEach(n => n.classList.remove("active"));
    el.classList.add("active");
    currentPage = page;
    load(page);
    if (window.innerWidth < 768) toggle_menu();
}

async function load(page) {
    const m = document.getElementById("main");
    m.innerHTML = \'<div class="spinner"></div>\';
    switch(page) {
        case "dashboard": m.innerHTML = await dashboard(); break;
        case "routes": m.innerHTML = await routes_page(); break;
        case "database": m.innerHTML = await database_page(); break;
        case "files": m.innerHTML = await files_page(); break;
        case "console": m.innerHTML = console_page(); break;
        case "seguranca": m.innerHTML = await seguranca_page(); break;
        case "auditoria": m.innerHTML = await auditoria_page(); break;
        case "logs": m.innerHTML = await logs_page(); break;
    }
}

async function dashboard() {
    const s = await api("/stats");
    return `<div class="page-header"><h2>Dashboard</h2></div>
    <div class="cards">
        <div class="card"><div class="card-label">PHP</div><div class="card-value">${s.php}</div></div>
        <div class="card"><div class="card-label">Rotas</div><div class="card-value">${s.routes}</div></div>
        <div class="card"><div class="card-label">Tabelas</div><div class="card-value">${s.tables}</div></div>
        <div class="card"><div class="card-label">Storage</div><div class="card-value">${s.storage}</div></div>
    </div>`;
}

async function routes_page() {
    const r = await api("/routes");
    let h = `<div class="page-header"><h2>Rotas</h2><button class="btn btn-primary" onclick="add_route()">Nova Rota</button></div><div id="route-list">`;
    Object.entries(r).forEach(([method, routes]) => {
        Object.entries(routes).forEach(([path, info]) => {
            const m = method.toLowerCase();
            h += `<div class="row"><span class="badge badge-${m}">${method}</span> <code>${path}</code>
            <button class="btn btn-sm btn-danger" onclick="del_route(\'${method}\',\'${path}\')">X</button></div>`;
        });
    });
    h += `</div><div id="route-edit" class="panel" style="display:none;margin-top:1rem"></div>`;
    return h;
}

async function add_route() {
    const ed = document.getElementById("route-edit");
    ed.style.display = "block";
    ed.innerHTML = `<h3>Nova Rota</h3>
    <div class="form-group"><label>Metodo</label><select id="re-method" class="input"><option>GET</option><option>POST</option><option>PUT</option><option>DELETE</option><option>PATCH</option><option>GET|POST</option></select></div>
    <div class="form-group"><label>Path</label><input id="re-path" class="input" placeholder="/users/:id"></div>
    <div class="form-group"><label>Codigo</label><textarea id="re-code" class="input" rows="8" placeholder="return $app->success([...]);"></textarea></div>
    <button class="btn btn-primary" onclick="save_route()">Guardar</button>
    <button class="btn" onclick="document.getElementById(\'route-edit\').style.display=\'none\'">Cancelar</button>`;
}

async function save_route() {
    const method = document.getElementById("re-method").value;
    const path = document.getElementById("re-path").value;
    const code = document.getElementById("re-code").value;
    await api("/routes", {method:"POST", body:JSON.stringify({method,path,code})});
    load("routes");
}

async function del_route(method, path) {
    if (!confirm("Apagar " + method + " " + path + "?")) return;
    await api("/routes/delete", {method:"POST", body:JSON.stringify({method,path})});
    load("routes");
}

async function database_page() {
    let tables;
    let h = `<div class="page-header"><h2>Base de Dados</h2></div>`;
    try { tables = await api("/db/tables"); } catch(e) { return h + "<p>Sem base de dados.</p>"; }
    h += `<div class="db-tables">`;
    tables.forEach(t => h += `<button class="btn btn-table" onclick="browse_table(\'${t}\')">${t}</button>`);
    h += `</div>
    <div class="panel"><h3>Consola SQL</h3>
    <textarea id="sql-query" class="input" rows="4" placeholder="SELECT * FROM users LIMIT 10"></textarea>
    <button class="btn btn-primary" onclick="run_query()">Executar</button></div>
    <div id="query-result" class="result-table"></div>
    <div id="table-browse" style="margin-top:1rem"></div>`;
    return h;
}

async function browse_table(table) {
    const data = await api("/db/table/" + table);
    let h = `<div class="panel"><h3>${table} (${data.length} registos)</h3><div class="result-table"><table><thead><tr>`;
    if (data.length > 0) {
        Object.keys(data[0]).forEach(k => h += `<th>${k}</th>`);
        h += "</tr></thead><tbody>";
        data.forEach(row => { h += "<tr>"; Object.values(row).forEach(v => h += `<td>${v !== null ? v : "<span class=\"null\">NULL</span>"}</td>`); h += "</tr>"; });
    }
    h += "</tbody></table></div></div>";
    document.getElementById("table-browse").innerHTML = h;
}

async function run_query() {
    const sql = document.getElementById("sql-query").value;
    const result = await api("/db/query", {method:"POST", body:JSON.stringify({sql})});
    const div = document.getElementById("query-result");
    if (result.error) { div.innerHTML = `<div class="alert alert-error">${result.error}</div>`; return; }
    if (!result.rows || result.rows.length === 0) { div.innerHTML = "<p>Sem resultados.</p>"; return; }
    let h = \'<div class="result-table"><table><thead><tr>\';
    Object.keys(result.rows[0]).forEach(k => h += `<th>${k}</th>`);
    h += "</tr></thead><tbody>";
    result.rows.forEach(row => { h += "<tr>"; Object.values(row).forEach(v => h += `<td>${v !== null ? v : "<span class=\"null\">NULL</span>"}</td>`); h += "</tr>"; });
    h += "</tbody></table></div>";
    div.innerHTML = h;
}

async function files_page() {
    const files = await api("/files");
    let h = `<div class="page-header"><h2>Ficheiros</h2></div><div class="file-tree">`;
    Object.entries(files).forEach(([dir, items]) => {
        h += `<h3>${dir}/</h3>`;
        items.forEach(f => h += `<div class="file-item" onclick="open_file(\'${dir}\',\'${f}\')">${f}</div>`);
    });
    h += `</div><div id="file-editor" style="margin-top:1rem"></div>`;
    return h;
}

async function open_file(dir, name) {
    const data = await api("/file?dir=" + dir + "&name=" + name);
    document.getElementById("file-editor").innerHTML = `
    <div class="panel"><h3>${dir}/${name}</h3>
    <textarea id="file-content" class="input code" rows="20">${escape_html(data.content || "")}</textarea>
    <button class="btn btn-primary" onclick="save_file(\'${dir}\',\'${name}\')">Guardar</button></div>`;
}

async function save_file(dir, name) {
    const content = document.getElementById("file-content").value;
    await api("/file", {method:"POST", body:JSON.stringify({dir,name,content})});
    alert("Guardado.");
}

function console_page() {
    return `<div class="page-header"><h2>Consola API</h2></div>
    <div class="panel"><div class="form-row">
        <select id="con-method" class="input" style="width:auto"><option>GET</option><option>POST</option><option>PUT</option><option>DELETE</option><option>PATCH</option></select>
        <input id="con-url" class="input" placeholder="/api/v1/endpoint" style="flex:1">
    </div>
    <textarea id="con-headers" class="input" rows="2" placeholder=\'{"Content-Type":"application/json"}\'></textarea>
    <textarea id="con-body" class="input" rows="4" placeholder=\'{"key":"value"}\'></textarea>
    <button class="btn btn-primary" onclick="send_request()">Enviar</button></div>
    <div id="con-result" style="margin-top:1rem"></div>`;
}

async function send_request() {
    const method = document.getElementById("con-method").value;
    const url = document.getElementById("con-url").value;
    const headers = JSON.parse(document.getElementById("con-headers").value || "{}");
    const body = document.getElementById("con-body").value;
    const result = await api("/request", {method:"POST", body:JSON.stringify({method,url,headers,body})});
    document.getElementById("con-result").innerHTML = `<div class="panel"><h3>Resposta</h3><pre class="code-block">${JSON.stringify(result, null, 2)}</pre></div>`;
}

async function logs_page() {
    const logs = await api("/logs");
    return `<div class="page-header"><h2>Logs da Aplicacao</h2></div><pre class="log-viewer">${escape_html(logs)}</pre>`;
}

async function seguranca_page() {
    return `<div class="page-header"><h2>Seguranca</h2></div>
    <div class="cards-grid">
        <div class="panel">
            <h3>Autenticacao 2FA (TOTP)</h3>
            <button class="btn btn-primary" onclick="totp_gen()">Gerar Segredo</button>
            <div id="totp-result" class="result-block"></div>
        </div>
        <div class="panel">
            <h3>Encriptacao AES-256</h3>
            <textarea id="enc-input" class="input" rows="3" placeholder="Texto para encriptar ou desencriptar..."></textarea>
            <div class="btn-group"><button class="btn btn-primary" onclick="encrypt_data()">Encriptar</button><button class="btn" onclick="decrypt_data()">Desencriptar</button></div>
            <div id="enc-result" class="result-block"></div>
        </div>
        <div class="panel">
            <h3>Politica de Passwords</h3>
            <input id="pwd-test" class="input" placeholder="Testar password..." onkeyup="test_pwd()">
            <div id="pwd-result" class="result-block"></div>
        </div>
        <div class="panel">
            <h3>Backups</h3>
            <div class="form-row"><select id="backup-type" class="input"><option value="full">Completo</option><option value="db">Base de dados</option><option value="files">Ficheiros</option></select>
            <button class="btn btn-primary" onclick="run_backup()">Criar Backup</button></div>
            <div id="backup-result" class="result-block"></div>
        </div>
        <div class="panel">
            <h3>Alerta de Incidente</h3>
            <div class="form-row"><select id="alert-level" class="input" style="width:auto"><option>info</option><option>warning</option><option>critical</option></select>
            <input id="alert-msg" class="input" placeholder="Mensagem..."></div>
            <button class="btn btn-primary" onclick="send_alert()">Enviar Alerta</button>
            <div id="alert-result" class="result-block"></div>
        </div>
    </div>`;
}

async function auditoria_page() {
    const stats = await api("/nis2");
    const r = await api("/nis2/audit");
    return `<div class="page-header"><h2>Auditoria</h2></div>
    <div class="cards">
        <div class="card"><div class="card-label">Eventos</div><div class="card-value">${stats.audit_count}</div></div>
        <div class="card"><div class="card-label">Lockouts</div><div class="card-value">${stats.lockouts}</div></div>
        <div class="card"><div class="card-label">Backups</div><div class="card-value">${stats.backups}</div></div>
        <div class="card"><div class="card-label">Alertas</div><div class="card-value">${stats.alerts}</div></div>
    </div>
    <div class="panel" style="margin-top:1rem"><h3>Registo de Auditoria</h3>
    <div class="audit-log" id="audit-log">${(r.entries||[]).map(e => `<div class="audit-row"><span class="audit-ts">${(e.ts||"").replace("T"," ").substring(0,19)}</span> <span class="audit-action">${e.action}</span> <span class="audit-meta">${e.ip||""} ${e.user||""}</span></div>`).join("") || "Sem eventos."}</div></div>`;
}

async function totp_gen() {
    const r = await api("/nis2/totp");
    document.getElementById("totp-result").innerHTML = `
        <div class="kv"><span>Segredo</span><code>${r.secret}</code></div>
        <div class="kv"><span>Codigo atual</span><strong style="font-size:1.3rem">${r.code}</strong></div>
        <small>Use este segredo no Google Authenticator. Renova a cada 30s.</small>`;
}

async function encrypt_data() {
    const v = document.getElementById("enc-input").value;
    if (!v) return;
    const r = await api("/nis2/encrypt", {method:"POST", body:JSON.stringify({data:v})});
    document.getElementById("enc-result").innerHTML = `<code class="break">${escape_html(r.result)}</code>`;
}

async function decrypt_data() {
    const v = document.getElementById("enc-input").value;
    if (!v) return;
    const r = await api("/nis2/decrypt", {method:"POST", body:JSON.stringify({data:v})});
    document.getElementById("enc-result").innerHTML = `<code class="break">${escape_html(r.result)}</code>`;
}

async function test_pwd() {
    const v = document.getElementById("pwd-test").value;
    const r = await api("/nis2/policy", {method:"POST", body:JSON.stringify({password:v})});
    const d = document.getElementById("pwd-result");
    if (r.valid) d.innerHTML = \'<span class="text-success">Password valida.</span>\';
    else d.innerHTML = r.errors.map(e => `<div class="text-error">${e}</div>`).join("");
}

async function run_backup() {
    const el = document.getElementById("backup-result");
    el.innerHTML = "A criar backup...";
    const t = document.getElementById("backup-type").value;
    const r = await api("/nis2/backup", {method:"POST", body:JSON.stringify({type:t})});
    el.innerHTML = r.ok ? `<span class="text-success">Backup criado: ${r.file}</span>` : `<span class="text-error">Falhou.</span>`;
}

async function send_alert() {
    const level = document.getElementById("alert-level").value;
    const msg = document.getElementById("alert-msg").value;
    const r = await api("/nis2/alert", {method:"POST", body:JSON.stringify({level,msg})});
    document.getElementById("alert-result").innerHTML = r.ok ? \'<span class="text-success">Alerta enviado.</span>\' : \'<span class="text-error">Falhou.</span>\';
}

function escape_html(s) { return s.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;"); }

load("dashboard");
</script>');
    }

    private function html_shell(string $body, bool $full = true): string
    {
        $nav = $full ? '' : '';
        $style = '
        :root{--bg:#0d1117;--surface:#161b22;--border:#30363d;--text:#e6edf3;--muted:#8b949e;--primary:#3b82f6;--primary-hover:#2563eb;--success:#238636;--danger:#da3633;--warning:#d29922}
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Helvetica,Arial,sans-serif;background:var(--bg);color:var(--text);min-height:100vh;line-height:1.5}
        a{color:var(--primary);text-decoration:none}

        .app{display:flex;min-height:100vh}
        .overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:20}
        .overlay.show{display:block}
        .menu-toggle{display:none;position:fixed;top:.75rem;left:.75rem;z-index:30;background:var(--surface);border:1px solid var(--border);padding:.5rem .6rem;border-radius:6px;cursor:pointer;flex-direction:column;gap:4px}
        .menu-toggle .bar{width:20px;height:2px;background:var(--text);border-radius:1px}

        .nav{width:240px;background:var(--surface);border-right:1px solid var(--border);padding:1rem;display:flex;flex-direction:column;gap:2px;position:fixed;top:0;left:0;bottom:0;z-index:25;overflow-y:auto;transition:transform .2s}
        .nav-brand{font-size:15px;font-weight:600;padding:.5rem 0 1rem;color:var(--primary);letter-spacing:-.3px}
        .nav-item{padding:.5rem .75rem;border-radius:6px;cursor:pointer;color:var(--muted);font-size:13px;transition:all .12s;border:none;background:none;text-align:left}
        .nav-item:hover{background:#1c2333;color:var(--text)}
        .nav-item.active{background:#1f2a45;color:var(--primary);font-weight:500}
        .nav-footer{margin-top:auto;padding-top:1rem;border-top:1px solid var(--border)}
        .logout-link{color:var(--muted);font-size:12px}
        .logout-link:hover{color:var(--danger)}

        main{flex:1;margin-left:240px;padding:1.5rem 2rem;max-width:1200px}
        .page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:.5rem}
        .page-header h2{font-size:1.25rem;font-weight:600;color:var(--text);margin:0}
        h3{font-size:1rem;font-weight:600;margin:0 0 .75rem;color:var(--text)}

        .cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:.75rem;margin-bottom:1.5rem}
        .card{background:var(--surface);padding:1rem 1.25rem;border-radius:8px;border:1px solid var(--border)}
        .card-label{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:.25rem}
        .card-value{font-size:1.5rem;font-weight:600;color:var(--text)}
        .cards-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:1rem}

        .panel{background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:1rem 1.25rem;margin-bottom:.75rem}

        .btn{display:inline-flex;align-items:center;gap:.35rem;background:var(--surface);color:var(--text);border:1px solid var(--border);padding:.4rem .85rem;border-radius:6px;font-size:13px;cursor:pointer;transition:all .12s;white-space:nowrap}
        .btn:hover{background:#21262d}
        .btn-primary{background:var(--primary);border-color:var(--primary);color:#fff}
        .btn-primary:hover{background:var(--primary-hover)}
        .btn-danger{color:var(--danger);border-color:var(--danger);font-size:11px;padding:.2rem .5rem}
        .btn-danger:hover{background:var(--danger);color:#fff}
        .btn-sm{padding:.2rem .5rem;font-size:11px}
        .btn-group{display:flex;gap:.35rem;margin:.5rem 0}
        .btn-table{padding:.25rem .6rem;font-size:12px}
        .form-group{margin-bottom:.75rem}
        .form-group label{display:block;font-size:12px;color:var(--muted);margin-bottom:.25rem}
        .form-row{display:flex;gap:.5rem;align-items:center;margin-bottom:.5rem}

        .input,select,textarea{background:var(--bg);border:1px solid var(--border);color:var(--text);padding:.45rem .65rem;border-radius:6px;font-size:13px;font-family:inherit;width:100%;outline:none;transition:border-color .12s}
        .input:focus,select:focus,textarea:focus{border-color:var(--primary)}
        textarea{resize:vertical;font-family:"SF Mono",Menlo,monospace;font-size:12px}
        .code{font-family:"SF Mono",Menlo,monospace;font-size:12px}
        code{font-family:"SF Mono",Menlo,monospace;background:var(--bg);padding:.15rem .4rem;border-radius:3px;font-size:12px;color:var(--warning)}
        .break{word-break:break-all}

        table{width:100%;border-collapse:collapse;font-size:12px}
        th{background:var(--bg);color:var(--muted);font-weight:600;padding:.4rem .6rem;border:1px solid var(--border);text-align:left}
        td{padding:.35rem .6rem;border:1px solid var(--border)}
        tr:hover td{background:rgba(255,255,255,.02)}
        .result-table{overflow-x:auto;max-height:400px;overflow-y:auto;border-radius:6px;border:1px solid var(--border)}
        .null{color:var(--muted);font-style:italic}

        .badge{display:inline-block;padding:1px 6px;border-radius:3px;font-size:10px;font-weight:700;margin-right:.5rem;min-width:40px;text-align:center;text-transform:uppercase}
        .badge-get{background:#1b3d2f;color:#3fb950}.badge-post{background:#1a3040;color:#58a6ff}
        .badge-put{background:#3d2e00;color:#d29922}.badge-delete{background:#3d1f1f;color:#da3633}
        .badge-patch{background:#1a3038;color:#50e3c2}
        .row{display:flex;align-items:center;padding:.3rem 0;border-bottom:1px solid var(--border);gap:.4rem;font-size:13px}
        .row code{background:none;padding:0;color:var(--text)}
        .db-tables{display:flex;gap:.35rem;flex-wrap:wrap;margin-bottom:1rem}
        .file-tree{background:var(--surface);padding:.75rem 1rem;border-radius:8px;border:1px solid var(--border)}
        .file-tree h3{font-size:13px;color:var(--primary);margin:.5rem 0 .25rem}
        .file-item{padding:.2rem .4rem;cursor:pointer;border-radius:4px;font-size:13px;color:var(--muted)}
        .file-item:hover{background:var(--bg);color:var(--text)}
        .log-viewer,.code-block{background:var(--bg);padding:1rem;border-radius:6px;font-size:12px;max-height:500px;overflow-y:auto;white-space:pre-wrap;color:var(--muted);font-family:"SF Mono",Menlo,monospace;border:1px solid var(--border)}
        .result-block{margin-top:.5rem;font-size:13px}
        .kv{display:flex;justify-content:space-between;padding:.25rem 0;font-size:13px;border-bottom:1px solid var(--border)}
        .text-success{color:var(--success)}.text-error{color:var(--danger)}
        .alert{padding:.5rem .75rem;border-radius:6px;font-size:13px}
        .alert-error{background:#3d1f1f;color:var(--danger);border:1px solid var(--danger)}

        .audit-log{max-height:400px;overflow-y:auto}
        .audit-row{display:flex;gap:.75rem;padding:.25rem 0;border-bottom:1px solid var(--border);font-size:12px}
        .audit-ts{color:var(--muted);white-space:nowrap}
        .audit-action{color:var(--primary);font-weight:500;white-space:nowrap}
        .audit-meta{color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

        .spinner{width:24px;height:24px;border:2px solid var(--border);border-top-color:var(--primary);border-radius:50%;animation:spin .6s linear infinite;margin:2rem auto}
        @keyframes spin{to{transform:rotate(360deg)}}

        .login-box{max-width:380px;margin:80px auto;background:var(--surface);padding:2rem;border-radius:12px;text-align:center;border:1px solid var(--border)}
        .login-box h1{color:var(--primary);margin-bottom:1.25rem;font-size:1.25rem;font-weight:600}
        .login-box input{margin:.5rem 0;text-align:center;font-size:14px}
        .login-box button{width:100%;margin-top:.75rem;font-size:14px;padding:.55rem}

        @media(max-width:768px){
            .menu-toggle{display:flex}
            .nav{transform:translateX(-100%)}
            .nav.open{transform:translateX(0)}
            main{margin-left:0;padding:1rem;padding-top:3rem}
            .cards{grid-template-columns:repeat(2,1fr)}
            .cards-grid{grid-template-columns:1fr}
            .page-header{flex-direction:column;align-items:flex-start}
        }
        @media(max-width:480px){
            .cards{grid-template-columns:1fr}
        }
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
