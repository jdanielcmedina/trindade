<?php
namespace Trindade\Plugins;

class Studio
{
    private $app;

    public function __construct($app)
    {
        $this->app = $app;
        $app->group('/studio', function () use ($app) {
            $app->on('GET /', [$this, 'index']);
            $app->on('GET /login', [$this, 'login']);
            $app->on('POST /login', [$this, 'do_login']);
            $app->on('GET /logout', [$this, 'logout']);
            $app->on('GET /api/stats', [$this, 'api_stats']);
            $app->on('GET /api/routes', [$this, 'api_routes']);
            $app->on('POST /api/routes', [$this, 'api_route_save']);
            $app->on('POST /api/routes/delete', [$this, 'api_route_delete']);
            $app->on('POST /api/routes/validate', [$this, 'api_route_validate']);
            $app->on('GET /api/db/tables', [$this, 'api_db_tables']);
            $app->on('GET /api/db/table/:table', [$this, 'api_db_table']);
            $app->on('POST /api/db/query', [$this, 'api_db_query']);
            $app->on('GET /api/files', [$this, 'api_files']);
            $app->on('GET /api/file', [$this, 'api_file_get']);
            $app->on('POST /api/file', [$this, 'api_file_save']);
            $app->on('GET /api/logs', [$this, 'api_logs']);
            $app->on('POST /api/request', [$this, 'api_request']);
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

    private function check(): void
    {
        if (!$this->auth()) { echo json_encode(['error' => 'Unauthorized']); exit; }
    }

    public function login() { $this->page('login'); }
    public function logout() { $this->app->session('studio_auth', false); $this->app->redirect('/studio/login'); }

    public function do_login()
    {
        $pass = $this->app->config('studio')['password'] ?? 'trindade';
        $input = $this->app->request('password');
        if ($input === $pass) {
            $this->app->session('studio_auth', md5($pass));
            echo json_encode(['ok' => true]);
        } else {
            echo json_encode(['ok' => false, 'error' => 'Password invalida']);
        }
    }

    public function index()
    {
        if (!$this->auth()) { $this->app->redirect('/studio/login'); return; }
        $page = $_GET['p'] ?? 'dashboard';
        $this->page($page);
    }

    private function page(string $view)
    {
        $title = match($view) {
            'dashboard' => 'Dashboard',
            'routes' => 'Rotas',
            'database' => 'Base de Dados',
            'files' => 'Ficheiros',
            'console' => 'Consola API',
            'security' => 'Seguranca',
            'audit' => 'Auditoria',
            'logs' => 'Logs',
            'workflow' => 'Workflows',
            default => 'Trindade Studio',
        };

        $isLogin = $view === 'login';
        $nav = !$isLogin;

        $navItems = [
            ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => $this->icon('dashboard')],
            ['id' => 'workflow', 'label' => 'Workflows', 'icon' => $this->icon('bolt')],
            ['id' => 'routes', 'label' => 'Rotas', 'icon' => $this->icon('globe')],
            ['id' => 'database', 'label' => 'Base Dados', 'icon' => $this->icon('db')],
            ['id' => 'files', 'label' => 'Ficheiros', 'icon' => $this->icon('file')],
            ['id' => 'console', 'label' => 'Consola', 'icon' => $this->icon('terminal')],
            ['id' => 'security', 'label' => 'Seguranca', 'icon' => $this->icon('lock')],
            ['id' => 'audit', 'label' => 'Auditoria', 'icon' => $this->icon('audit')],
            ['id' => 'logs', 'label' => 'Logs', 'icon' => $this->icon('logs')],
        ];

        ob_start();
        if ($view === 'login') {
            $this->render_login();
        } else {
            $this->render_page($view, $navItems, $title);
        }
        $body = ob_get_clean();

        $nav_html = '';
        if ($nav) {
            foreach ($navItems as $item) {
                $active = $view === $item['id'];
                $nav_html .= '<a href="/studio?p=' . $item['id'] . '" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] transition-all duration-150 '
                    . ($active ? 'bg-indigo-500/10 text-indigo-400 font-medium' : 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800/50')
                    . '">' . $item['icon'] . '<span>' . $item['label'] . '</span></a>';
            }
        }

        echo '<!DOCTYPE html><html lang="pt"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>' . htmlspecialchars($title) . ' — Trindade Studio</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{fontFamily:{sans:["Inter","-apple-system","BlinkMacSystemFont","Segoe UI",sans-serif],mono:["JetBrains Mono","SF Mono","Menlo",monospace]}}}};tailwind.config.darkMode="class";</script>
<style>
  *,::before,::after{--tw-border-spacing-x:0;--tw-border-spacing-y:0}
  body{font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#09090b;color:#fafafa;-webkit-font-smoothing:antialiased}
  ::-webkit-scrollbar{width:6px;height:6px}
  ::-webkit-scrollbar-track{background:transparent}
  ::-webkit-scrollbar-thumb{background:#27272a;border-radius:3px}
</style>
</head><body class="bg-zinc-950 text-zinc-100 antialiased">';

if ($nav) {
    echo '<div class="flex h-screen overflow-hidden">
    <aside class="w-60 border-r border-zinc-800 bg-zinc-950 flex flex-col shrink-0">
        <div class="flex items-center gap-2.5 px-5 h-14 border-b border-zinc-800 shrink-0">
            <div class="w-6 h-6 rounded-md bg-indigo-500 flex items-center justify-center">
                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <span class="text-sm font-semibold tracking-tight">Trindade</span>
            <span class="text-[11px] text-zinc-500 font-medium ml-auto">Studio</span>
        </div>
        <nav class="flex-1 px-2 py-3 space-y-0.5 overflow-y-auto">' . $nav_html . '</nav>
        <div class="px-4 py-3 border-t border-zinc-800">
            <a href="/studio/logout" class="text-[12px] text-zinc-500 hover:text-red-400 transition-colors">Terminar sessao</a>
        </div>
    </aside>
    <main class="flex-1 overflow-y-auto"><div class="max-w-5xl mx-auto px-8 py-8">' . $body . '</div></main>
</div>';
} else {
    echo $body;
}

echo '</body></html>';
    }

    private function icon(string $name): string
    {
        return match($name) {
            'dashboard' => '<svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>',
            'bolt' => '<svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>',
            'globe' => '<svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"/></svg>',
            'db' => '<svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75c0 2.278-3.694 4.125-8.25 4.125S3.75 12.403 3.75 10.125V6.375"/></svg>',
            'file' => '<svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>',
            'terminal' => '<svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0021 18V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v12a2.25 2.25 0 002.25 2.25z"/></svg>',
            'lock' => '<svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>',
            'audit' => '<svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 16.5h4.5M9.75 13.5h4.5"/></svg>',
            'logs' => '<svg class="w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>',
            default => '',
        };
    }

    private function render_login()
    {
        echo '<div class="min-h-screen flex items-center justify-center bg-zinc-950">
        <div class="w-full max-w-sm">
            <div class="text-center mb-8">
                <div class="w-10 h-10 rounded-xl bg-indigo-500 inline-flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <h1 class="text-xl font-semibold tracking-tight">Trindade Studio</h1>
                <p class="text-sm text-zinc-500 mt-1">Enter your password to continue</p>
            </div>
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6">
                <input type="password" id="login-pwd" placeholder="Password" autofocus onkeydown="if(event.key===\'Enter\')login()"
                    class="w-full bg-zinc-950 border border-zinc-800 rounded-lg px-4 py-2.5 text-sm text-center outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all placeholder:text-zinc-600">
                <div id="login-err" class="text-xs text-red-400 mt-2 text-center hidden"></div>
                <button onclick="login()" class="w-full mt-3 bg-white text-black rounded-lg py-2.5 text-sm font-semibold hover:bg-zinc-200 transition-colors">Entrar</button>
            </div>
        </div>
        <script>
        async function login() {
            const pwd = document.getElementById("login-pwd").value;
            const err = document.getElementById("login-err");
            const r = await fetch("/studio/login", {method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:"password="+encodeURIComponent(pwd)});
            const d = await r.json();
            if (d.ok) location.href = "/studio";
            else { err.textContent = "Password invalida"; err.classList.remove("hidden"); }
        }
        </script></div>';
    }

    private function render_page(string $view, array $nav, string $title)
    {
        switch ($view) {
            case 'dashboard': $this->view_dashboard(); break;
            case 'routes': $this->view_routes(); break;
            case 'database': $this->view_database(); break;
            case 'files': $this->view_files(); break;
            case 'console': $this->view_console(); break;
            case 'security': $this->view_security(); break;
            case 'audit': $this->view_audit(); break;
            case 'logs': $this->view_logs(); break;
            case 'workflow': $this->view_workflow(); break;
        }
    }

    private function view_dashboard()
    {
        $stats = ['php' => PHP_VERSION, 'routes' => 0, 'tables' => 0, 'storage' => '-'];
        try {
            $r = $this->app->routes();
            foreach ($r as $m => $rs) $stats['routes'] += count($rs);
        } catch (\Throwable $e) {}
        if ($this->app->db) {
            try { $t = $this->app->db->query("SHOW TABLES")->fetchAll(); $stats['tables'] = count($t); } catch (\Throwable $e) {}
        }
        $st = $this->app->storage();
        if (is_dir($st)) {
            $size = 0;
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($st, \RecursiveDirectoryIterator::SKIP_DOTS)) as $f) $size += $f->getSize();
            $stats['storage'] = $size > 1048576 ? round($size / 1048576, 1) . ' MB' : round($size / 1024, 1) . ' KB';
        }

        echo '<div class="mb-8"><h1 class="text-lg font-semibold tracking-tight">Dashboard</h1><p class="text-sm text-zinc-500 mt-1">Overview</p></div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">';
        foreach ([
            ['label' => 'PHP Version', 'value' => $stats['php'], 'color' => 'indigo'],
            ['label' => 'Routes', 'value' => $stats['routes'], 'color' => 'emerald'],
            ['label' => 'DB Tables', 'value' => $stats['tables'], 'color' => 'blue'],
            ['label' => 'Storage', 'value' => $stats['storage'], 'color' => 'amber'],
        ] as $c) {
            echo '<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 hover:border-zinc-700 transition-all duration-200">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-medium uppercase tracking-widest text-zinc-500">' . $c['label'] . '</span>
                </div>
                <div class="text-2xl font-bold tracking-tight">' . htmlspecialchars((string)$c['value']) . '</div>
            </div>';
        }
        echo '</div>';
    }

    private function view_routes()
    {
        $routes = $this->app->routes();
        $total = 0;
        foreach ($routes as $rs) $total += count($rs);

        $badge = fn($m) => match($m) {
            'GET' => 'bg-emerald-500/10 text-emerald-400',
            'POST' => 'bg-blue-500/10 text-blue-400',
            'PUT' => 'bg-amber-500/10 text-amber-400',
            'DELETE' => 'bg-red-500/10 text-red-400',
            'PATCH' => 'bg-sky-500/10 text-sky-400',
            default => 'bg-zinc-800 text-zinc-400',
        };

        echo '<div class="flex items-center justify-between mb-8">
            <div><h1 class="text-lg font-semibold tracking-tight">Rotas</h1><p class="text-sm text-zinc-500 mt-1">' . $total . ' endpoints</p></div>
            <button onclick="document.getElementById(\'route-form\').classList.toggle(\'hidden\')" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-black text-[13px] font-semibold rounded-lg hover:bg-zinc-200 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.5v15m7.5-7.5h-15"/></svg> Nova Rota
            </button></div>
        <div id="route-form" class="hidden mb-6 bg-zinc-900 border border-zinc-800 rounded-xl p-5">
            <h3 class="text-sm font-semibold mb-4">Nova Rota</h3>
            <div class="flex gap-3 mb-4">
                <select id="re-method" class="w-28 bg-zinc-950 border border-zinc-800 rounded-lg px-3 py-2 text-xs font-mono font-medium outline-none focus:border-indigo-500">
                    <option>GET</option><option>POST</option><option>PUT</option><option>DELETE</option><option>PATCH</option>
                </select>
                <input id="re-path" placeholder="/users/:id" class="flex-1 bg-zinc-950 border border-zinc-800 rounded-lg px-3 py-2 text-xs font-mono outline-none focus:border-indigo-500 placeholder:text-zinc-600">
            </div>
            <textarea id="re-code" rows="6" placeholder="$data = $app->body();" class="w-full bg-zinc-950 border border-zinc-800 rounded-lg px-4 py-3 text-xs font-mono leading-relaxed outline-none focus:border-indigo-500 resize-y mb-4"></textarea>
            <div id="re-status" class="mb-4"></div>
            <div class="flex gap-2">
                <button onclick="save_route()" class="px-4 py-2 bg-white text-black text-[13px] font-semibold rounded-lg hover:bg-zinc-200 transition-colors">Guardar</button>
                <button onclick="validate_route()" class="px-4 py-2 bg-zinc-800 border border-zinc-700 text-[13px] rounded-lg hover:bg-zinc-700 transition-colors">Validar</button>
                <button onclick="document.getElementById(\'route-form\').classList.add(\'hidden\')" class="px-4 py-2 text-[13px] text-zinc-500 hover:text-zinc-300">Cancelar</button>
            </div>
        </div>
        <div class="space-y-px">';
        foreach ($routes as $m => $rs) {
            foreach ($rs as $path => $info) {
                echo '<div class="group flex items-center gap-3 px-4 py-2.5 bg-zinc-900 border border-zinc-800 first:rounded-t-lg last:rounded-b-lg hover:bg-zinc-800/50 transition-colors">
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wide ' . $badge($m) . '">' . $m . '</span>
                    <code class="flex-1 text-[13px] font-mono">' . htmlspecialchars($path) . '</code>
                    <button onclick="del_route(\'' . $m . '\',\'' . addslashes($path) . '\')" class="text-[11px] text-zinc-500 hover:text-red-400 opacity-0 group-hover:opacity-100 transition-all">Remove</button>
                </div>';
            }
        }
        echo '</div>
        <script>
        async function save_route() {
            const m = document.getElementById("re-method").value;
            const p = document.getElementById("re-path").value;
            const c = document.getElementById("re-code").value;
            await fetch("/studio/api/routes", {method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({method:m,path:p,code:c})});
            location.reload();
        }
        async function validate_route() {
            const m = document.getElementById("re-method").value;
            const c = document.getElementById("re-code").value;
            const st = document.getElementById("re-status");
            const r = await fetch("/studio/api/routes/validate", {method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({method:m,code:c})});
            const d = await r.json();
            if (d.ready) st.innerHTML = \'<div class="p-3 rounded-lg text-xs bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Pronto para producao</div>\';
            else st.innerHTML = \'<div class="p-3 rounded-lg text-xs bg-red-500/10 text-red-400 border border-red-500/20">\' + d.warnings.join("<br>") + \'</div>\';
        }
        async function del_route(m, p) {
            if (!confirm("Apagar " + m + " " + p + "?")) return;
            await fetch("/studio/api/routes/delete", {method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({method:m,path:p})});
            location.reload();
        }
        </script>';
    }

    private function view_database()
    {
        $tables = [];
        if ($this->app->db) {
            try { $rows = $this->app->db->query("SHOW TABLES")->fetchAll(\PDO::FETCH_NUM); $tables = array_map(fn($r) => $r[0], $rows); } catch (\Throwable $e) {}
        }
        echo '<div class="mb-8"><h1 class="text-lg font-semibold tracking-tight">Base de Dados</h1><p class="text-sm text-zinc-500 mt-1">' . count($tables) . ' tables</p></div>
        <div class="flex flex-wrap gap-1.5 mb-6">';
        foreach ($tables as $t) {
            echo '<button onclick="browse(\'' . $t . '\')" class="px-3 py-1.5 text-[12px] font-mono rounded-lg bg-zinc-900 border border-zinc-800 hover:border-zinc-700 hover:bg-zinc-800 transition-all text-zinc-400 hover:text-zinc-100">' . htmlspecialchars($t) . '</button>';
        }
        echo '</div>
        <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 mb-6">
            <h3 class="text-sm font-semibold mb-3">SQL Console</h3>
            <textarea id="sql-query" rows="3" placeholder="SELECT * FROM users LIMIT 10" class="w-full bg-zinc-950 border border-zinc-800 rounded-lg px-4 py-3 text-xs font-mono outline-none focus:border-indigo-500 resize-y mb-3"></textarea>
            <button onclick="run_query()" class="px-4 py-2 bg-white text-black text-[13px] font-semibold rounded-lg hover:bg-zinc-200 transition-colors">Executar</button>
        </div>
        <div id="db-result" class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden hidden">
            <div class="overflow-x-auto max-h-96"><table class="w-full text-xs" id="db-table"></table></div>
        </div>
        <script>
        async function browse(t) {
            const r = await fetch("/studio/api/db/table/" + t).then(r => r.json());
            render_table(t, r.length, r);
        }
        async function run_query() {
            const sql = document.getElementById("sql-query").value;
            const r = await fetch("/studio/api/db/query", {method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({sql})}).then(r => r.json());
            if (r.rows) render_table("Query result", r.rows.length, r.rows);
        }
        function render_table(title, count, rows) {
            const div = document.getElementById("db-result");
            let h = \'<div class="px-5 py-3 border-b border-zinc-800 text-xs font-medium text-zinc-400">\' + title + " — " + count + \' rows</div>\';
            h += \'<table class="w-full text-xs">\';
            if (rows.length > 0) {
                h += \'<thead><tr class="bg-zinc-950">\';
                Object.keys(rows[0]).forEach(k => h += \'<th class="text-left px-4 py-2 text-zinc-500 font-medium font-mono text-[11px] uppercase tracking-wider">\' + k + \'</th>\');
                h += \'</tr></thead><tbody>\';
                rows.forEach(r => { h += \'<tr class="border-t border-zinc-800 hover:bg-zinc-800/50 transition-colors">\'; Object.values(r).forEach(v => h += \'<td class="px-4 py-2 font-mono text-zinc-400">\' + (v !== null ? v : \'<span class="text-zinc-600 italic">NULL</span>\') + \'</td>\'); h += \'</tr>\'; });
                h += \'</tbody>\';
            }
            h += \'</table>\';
            div.innerHTML = h;
            div.classList.remove("hidden");
        }
        </script>';
    }

    private function view_files()
    {
        $dirs = ['routes', 'helpers', 'views'];
        echo '<div class="mb-8"><h1 class="text-lg font-semibold tracking-tight">Ficheiros</h1></div><div class="grid grid-cols-1 lg:grid-cols-3 gap-4">';
        foreach ($dirs as $d) {
            $p = $this->app->path($d);
            echo '<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4"><h3 class="text-xs font-semibold uppercase tracking-wider text-indigo-400 mb-3">' . $d . '/</h3><div class="space-y-0.5">';
            if ($p && is_dir($p)) {
                foreach (scandir($p) as $f) {
                    if ($f === '.' || $f === '..' || $f === '.gitkeep') continue;
                    echo '<button onclick="open_file(\'' . $d . '\',\'' . addslashes($f) . '\')" class="w-full text-left px-3 py-1.5 text-[13px] text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800 rounded-lg transition-colors font-mono">' . htmlspecialchars($f) . '</button>';
                }
            }
            echo '</div></div>';
        }
        echo '</div><div id="file-editor"></div>
        <script>
        async function open_file(dir, name) {
            const r = await fetch("/studio/api/file?dir=" + dir + "&name=" + name).then(r => r.json());
            document.getElementById("file-editor").innerHTML = \'<div class="mt-6 bg-zinc-900 border border-zinc-800 rounded-xl p-5"><div class="flex items-center justify-between mb-3"><h3 class="text-sm font-semibold font-mono">\' + dir + \'/\' + name + \'</h3></div><textarea id="file-content" rows="22" class="w-full bg-zinc-950 border border-zinc-800 rounded-lg p-4 text-xs font-mono leading-relaxed outline-none focus:border-indigo-500 resize-y">\' + (r.content || "") + \'</textarea><button onclick="save_file(\\\'\' + dir + \'\\\',\\\'\' + name + \'\\\')" class="mt-3 px-4 py-2 bg-white text-black text-[13px] font-semibold rounded-lg hover:bg-zinc-200 transition-colors">Guardar</button></div>\';
        }
        async function save_file(dir, name) {
            const content = document.getElementById("file-content").value;
            await fetch("/studio/api/file", {method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({dir,name,content})});
            location.reload();
        }
        </script>';
    }

    private function view_console()
    {
        echo '<div class="mb-8"><h1 class="text-lg font-semibold tracking-tight">Consola API</h1></div>
        <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
            <div class="flex gap-3 mb-4">
                <select id="con-method" class="w-24 bg-zinc-950 border border-zinc-800 rounded-lg px-3 py-2 text-xs font-mono font-medium outline-none focus:border-indigo-500">
                    <option>GET</option><option>POST</option><option>PUT</option><option>DELETE</option><option>PATCH</option>
                </select>
                <input id="con-url" placeholder="/api/endpoint" class="flex-1 bg-zinc-950 border border-zinc-800 rounded-lg px-3 py-2 text-xs font-mono outline-none focus:border-indigo-500">
            </div>
            <textarea id="con-headers" rows="2" class="w-full bg-zinc-950 border border-zinc-800 rounded-lg px-4 py-3 text-xs font-mono outline-none focus:border-indigo-500 resize-y mb-3">{"Content-Type":"application/json"}</textarea>
            <textarea id="con-body" rows="4" placeholder=\'{"key":"value"}\' class="w-full bg-zinc-950 border border-zinc-800 rounded-lg px-4 py-3 text-xs font-mono outline-none focus:border-indigo-500 resize-y mb-4"></textarea>
            <button onclick="send_req()" class="px-4 py-2 bg-white text-black text-[13px] font-semibold rounded-lg hover:bg-zinc-200 transition-colors">Enviar</button>
        </div>
        <div id="con-result" class="mt-4"></div>
        <script>
        async function send_req() {
            const m = document.getElementById("con-method").value;
            const u = document.getElementById("con-url").value;
            const h = JSON.parse(document.getElementById("con-headers").value || "{}");
            const b = document.getElementById("con-body").value;
            const r = await fetch("/studio/api/request", {method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({method:m,url:u,headers:h,body:b})}).then(r => r.json());
            document.getElementById("con-result").innerHTML = \'<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5"><h3 class="text-sm font-semibold mb-3">Resposta</h3><pre class="text-xs font-mono text-zinc-400 whitespace-pre-wrap">\' + JSON.stringify(r, null, 2) + \'</pre></div>\';
        }
        </script>';
    }

    private function view_security()
    {
        echo '<div class="mb-8"><h1 class="text-lg font-semibold tracking-tight">Seguranca</h1></div><div class="grid grid-cols-1 lg:grid-cols-2 gap-4">';
        // TOTP
        echo '<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 hover:border-zinc-700 transition-colors">
            <h3 class="text-sm font-semibold mb-4">Autenticacao 2FA (TOTP)</h3>
            <button onclick="gen_totp()" class="px-4 py-2 bg-white text-black text-[13px] font-semibold rounded-lg hover:bg-zinc-200 transition-colors mb-3">Gerar Segredo</button>
            <div id="totp-result"></div></div>';
        // Encrypt
        echo '<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 hover:border-zinc-700 transition-colors">
            <h3 class="text-sm font-semibold mb-4">Encriptacao AES-256</h3>
            <textarea id="enc-input" rows="3" placeholder="Texto para encriptar/desencriptar..." class="w-full bg-zinc-950 border border-zinc-800 rounded-lg px-3 py-2 text-xs font-mono outline-none focus:border-indigo-500 resize-y mb-3"></textarea>
            <div class="flex gap-2 mb-3">
                <button onclick="encrypt()" class="px-3 py-1.5 text-[11px] font-medium rounded-lg bg-indigo-500 text-white hover:bg-indigo-600 transition-colors">Encriptar</button>
                <button onclick="decrypt()" class="px-3 py-1.5 text-[11px] rounded-lg bg-zinc-800 border border-zinc-700 hover:bg-zinc-700 transition-colors">Desencriptar</button>
            </div>
            <div id="enc-result" class="text-[11px] break-all text-zinc-400 font-mono"></div></div>';
        // Password Policy
        echo '<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 hover:border-zinc-700 transition-colors">
            <h3 class="text-sm font-semibold mb-4">Politica de Passwords</h3>
            <input id="pwd-test" placeholder="Testar password..." onkeyup="test_pwd()" class="w-full bg-zinc-950 border border-zinc-800 rounded-lg px-3 py-2 text-xs outline-none focus:border-indigo-500 mb-3">
            <div id="pwd-result" class="text-xs space-y-1"></div></div>';
        // Backup
        echo '<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 hover:border-zinc-700 transition-colors">
            <h3 class="text-sm font-semibold mb-4">Backups</h3>
            <div class="flex gap-2 mb-3">
                <select id="backup-type" class="bg-zinc-950 border border-zinc-800 rounded-lg px-3 py-2 text-xs outline-none focus:border-indigo-500">
                    <option value="full">Completo</option><option value="db">Base dados</option><option value="files">Ficheiros</option>
                </select>
                <button onclick="run_backup()" class="px-3 py-1.5 text-[11px] font-medium rounded-lg bg-indigo-500 text-white hover:bg-indigo-600 transition-colors">Criar</button>
            </div><div id="backup-result" class="text-xs text-zinc-400"></div></div>';
        // Alert
        echo '<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 hover:border-zinc-700 transition-colors">
            <h3 class="text-sm font-semibold mb-4">Alerta de Incidente</h3>
            <div class="flex gap-2 mb-3">
                <select id="alert-level" class="bg-zinc-950 border border-zinc-800 rounded-lg px-3 py-2 text-xs outline-none focus:border-indigo-500">
                    <option>info</option><option>warning</option><option>critical</option>
                </select>
                <input id="alert-msg" placeholder="Mensagem..." class="flex-1 bg-zinc-950 border border-zinc-800 rounded-lg px-3 py-2 text-xs outline-none focus:border-indigo-500">
            </div>
            <button onclick="send_alert()" class="px-3 py-1.5 text-[11px] font-medium rounded-lg bg-red-500 text-white hover:opacity-90 transition-opacity">Enviar</button>
            <div id="alert-result" class="text-xs text-emerald-400 mt-2"></div></div>';
        echo '</div><script>
        async function gen_totp() { const r = await fetch("/studio/api/nis2/totp").then(r => r.json()); document.getElementById("totp-result").innerHTML = \'<div class="space-y-2 text-xs"><div class="flex justify-between py-1.5 border-b border-zinc-800"><span class="text-zinc-500">Segredo</span><code class="text-amber-400 font-mono">\' + r.secret + \'</code></div><div class="flex justify-between py-1.5"><span class="text-zinc-500">Codigo</span><strong class="text-lg font-mono tracking-widest">\' + r.code + \'</strong></div></div>\'; }
        async function encrypt() { const v = document.getElementById("enc-input").value; const r = await fetch("/studio/api/nis2/encrypt", {method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({data:v})}).then(r => r.json()); document.getElementById("enc-result").innerHTML = \'<code>\' + r.result + \'</code>\'; }
        async function decrypt() { const v = document.getElementById("enc-input").value; const r = await fetch("/studio/api/nis2/decrypt", {method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({data:v})}).then(r => r.json()); document.getElementById("enc-result").innerHTML = \'<code>\' + r.result + \'</code>\'; }
        async function test_pwd() { const v = document.getElementById("pwd-test").value; const r = await fetch("/studio/api/nis2/policy", {method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({password:v})}).then(r => r.json()); const d = document.getElementById("pwd-result"); if (r.valid) d.innerHTML = \'<span class="text-emerald-400 font-medium">Password valida.</span>\'; else d.innerHTML = r.errors.map(e => \'<div class="text-red-400">\' + e + \'</div>\').join(""); }
        async function run_backup() { const t = document.getElementById("backup-type").value; const r = await fetch("/studio/api/nis2/backup", {method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({type:t})}).then(r => r.json()); document.getElementById("backup-result").innerHTML = r.ok ? "Criado: " + r.file : "Falhou"; }
        async function send_alert() { const l = document.getElementById("alert-level").value; const m = document.getElementById("alert-msg").value; await fetch("/studio/api/nis2/alert", {method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({level:l,msg:m})}); document.getElementById("alert-result").innerHTML = "Enviado."; }
        </script>';
    }

    private function view_audit()
    {
        $entries = $this->app->audit_log();
        $cache = $this->app->storage('cache');
        $lockouts = count(glob($cache . '/lockout_*.cache'));
        $backups = count(glob($this->app->storage('backups') . '/backup-*.zip'));
        $alerts = count(array_filter($entries, fn($e) => str_starts_with($e['action'] ?? '', 'alert.')));

        echo '<div class="mb-8"><h1 class="text-lg font-semibold tracking-tight">Auditoria</h1></div>
        <div class="grid grid-cols-4 gap-4 mb-6">';
        foreach ([['Eventos', count($entries), 'indigo'], ['Lockouts', $lockouts, 'amber'], ['Backups', $backups, 'blue'], ['Alertas', $alerts, 'red']] as $c) {
            echo '<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4"><div class="text-[11px] uppercase tracking-wider text-zinc-500 font-medium mb-1">' . $c[0] . '</div><div class="text-xl font-bold tracking-tight text-' . $c[2] . '-400">' . $c[1] . '</div></div>';
        }
        echo '</div>
        <div class="bg-zinc-900 border border-zinc-800 rounded-xl">
            <div class="flex items-center justify-between px-5 py-3 border-b border-zinc-800"><h3 class="text-sm font-semibold">Registo de Auditoria</h3>
            <a href="/studio?p=audit" class="text-xs text-indigo-400 hover:underline">Refresh</a></div>
            <div class="max-h-[60vh] overflow-y-auto">';
        foreach ($entries as $e) {
            echo '<div class="flex items-center gap-4 px-5 py-2.5 border-b border-zinc-800 hover:bg-zinc-800/50 transition-colors text-xs">
                <span class="text-zinc-500 font-mono whitespace-nowrap w-[140px]">' . htmlspecialchars(substr(str_replace('T', ' ', $e['ts'] ?? ''), 0, 19)) . '</span>
                <span class="text-indigo-400 font-medium whitespace-nowrap min-w-[120px]">' . htmlspecialchars($e['action'] ?? '') . '</span>
                <span class="text-zinc-500 truncate">' . htmlspecialchars($e['ip'] ?? '') . ' ' . htmlspecialchars($e['user'] ?? '') . '</span>
            </div>';
        }
        if (empty($entries)) echo '<div class="px-5 py-8 text-center text-zinc-500 text-xs">Sem eventos de auditoria.</div>';
        echo '</div></div>';
    }

    private function view_logs()
    {
        $log = $this->app->storage('logs') . '/app.log';
        $content = file_exists($log) ? htmlspecialchars(file_get_contents($log)) : 'No log entries.';
        echo '<div class="flex items-center justify-between mb-8"><h1 class="text-lg font-semibold tracking-tight">Logs</h1>
            <a href="/studio?p=logs" class="text-xs text-indigo-400 hover:underline">Refresh</a></div>
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
                <pre class="text-xs font-mono text-zinc-400 whitespace-pre-wrap leading-relaxed max-h-[70vh] overflow-y-auto">' . $content . '</pre>
            </div>';
    }

    private function view_workflow()
    {
        echo '<div class="mb-8"><h1 class="text-lg font-semibold tracking-tight">Workflows</h1><p class="text-sm text-zinc-500 mt-1">Visual route builder.</p></div>
        <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-12 text-center">
            <p class="text-zinc-500">Workflow editor loading...</p>
        </div>';
    }

    // ======================== API ENDPOINTS ========================

    public function api_stats()
    {
        $this->check();
        $routes = 0; foreach ($this->app->routes() as $rs) $routes += count($rs);
        $tables = 0; $storage = '-';
        if ($this->app->db) { try { $t = $this->app->db->query("SHOW TABLES")->fetchAll(); $tables = count($t); } catch (\Throwable $e) {} }
        $st = $this->app->storage();
        if (is_dir($st)) { $sz = 0; foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($st, \RecursiveDirectoryIterator::SKIP_DOTS)) as $f) $sz += $f->getSize(); $storage = $sz > 1048576 ? round($sz/1048576,1).' MB' : round($sz/1024,1).' KB'; }
        echo json_encode(['php' => PHP_VERSION, 'routes' => $routes, 'tables' => $tables, 'storage' => $storage]);
    }
    public function api_routes() { $this->check(); echo json_encode($this->app->routes()); }

    public function api_route_save()
    {
        $this->check();
        $d = json_decode(file_get_contents('php://input'), true);
        $file = $this->app->path('routes') . '/web.php';
        $line = "\$app->on('{$d['method']} {$d['path']}', function () use (\$app) {\n    {$d['code']}\n});\n";
        file_put_contents($file, file_get_contents($file) . "\n" . $line);
        echo json_encode(['ok' => true]);
    }

    public function api_route_delete()
    {
        $this->check();
        $d = json_decode(file_get_contents('php://input'), true);
        $file = $this->app->path('routes') . '/web.php';
        $c = file_get_contents($file);
        $em = preg_quote($d['method'], '/');
        $ep = preg_quote($d['path'], '/');
        $c = preg_replace("/\\\$app->on\\('{$em}\\s+{$ep}',\\s*function\\s*\\([^)]*\\)\\s*use\\s*\\([^)]*\\)\\s*\\{[^}]*\\}\\);/s", '', $c);
        file_put_contents($file, $c);
        echo json_encode(['ok' => true]);
    }

    public function api_route_validate()
    {
        $this->check();
        $d = json_decode(file_get_contents('php://input'), true);
        $code = $d['code'] ?? '';
        $tmp = tempnam(sys_get_temp_dir(), 'tr_') . '.php';
        file_put_contents($tmp, "<?php\n" . $code . "\n?>");
        exec("php -l " . escapeshellarg($tmp) . " 2>&1", $out, $exit);
        unlink($tmp);
        $warnings = [];
        if (in_array($d['method'] ?? '', ['POST','PUT','DELETE']) && ($d['auth'] ?? 'none') === 'none') $warnings[] = "Route sem autenticacao — vulneravel.";
        if (preg_match('/\$app->db/', $code) && ($d['auth'] ?? 'none') === 'none') $warnings[] = "Operacoes DB sem autenticacao.";
        if (!preg_match('/return/', $code)) $warnings[] = "Nenhum return encontrado.";
        echo json_encode(['ok' => $exit === 0, 'output' => implode("\n", $out), 'warnings' => $warnings, 'ready' => $exit === 0 && empty($warnings)]);
    }

    public function api_db_tables()
    {
        $this->check();
        if (!$this->app->db) { echo json_encode([]); return; }
        try { echo json_encode(array_map(fn($r) => $r[0], $this->app->db->query("SHOW TABLES")->fetchAll(\PDO::FETCH_NUM))); } catch (\Throwable $e) { echo json_encode([]); }
    }

    public function api_db_table()
    {
        $this->check();
        if (!$this->app->db) { echo json_encode([]); return; }
        try { echo json_encode($this->app->db->query("SELECT * FROM " . $this->app->param('table') . " LIMIT 100")->fetchAll()); } catch (\Throwable $e) { echo json_encode(['error' => $e->getMessage()]); }
    }

    public function api_db_query()
    {
        $this->check();
        $d = json_decode(file_get_contents('php://input'), true);
        if (!$this->app->db) { echo json_encode(['error' => 'No database']); return; }
        try { $r = $this->app->db->query($d['sql']); echo json_encode(['rows' => preg_match('/^\s*SELECT/i', $d['sql']) ? $r->fetchAll() : []]); } catch (\Throwable $e) { echo json_encode(['error' => $e->getMessage()]); }
    }

    public function api_files()
    {
        $this->check();
        $result = [];
        foreach (['routes', 'helpers', 'views'] as $d) {
            $p = $this->app->path($d);
            if ($p && is_dir($p)) {
                $files = [];
                foreach (scandir($p) as $f) { if ($f !== '.' && $f !== '..' && $f !== '.gitkeep') $files[] = $f; }
                if (!empty($files)) $result[$d] = $files;
            }
        }
        echo json_encode($result);
    }

    public function api_file_get() { $this->check(); $path = $this->app->path($_GET['dir']) . '/' . basename($_GET['name']); echo json_encode(['content' => file_exists($path) ? file_get_contents($path) : '']); }
    public function api_file_save() { $this->check(); $d = json_decode(file_get_contents('php://input'), true); file_put_contents($this->app->path($d['dir']) . '/' . basename($d['name']), $d['content']); echo json_encode(['ok' => true]); }
    public function api_logs() { $this->check(); $log = $this->app->storage('logs') . '/app.log'; echo json_encode(file_exists($log) ? file_get_contents($log) : ''); }
    public function api_request() { $this->check(); $d = json_decode(file_get_contents('php://input'), true); ob_start(); $this->app->run(); echo json_encode(['body' => ob_get_clean()]); }

    public function api_nis2()
    {
        $this->check();
        $entries = $this->app->audit_log(1000);
        $locks = count(glob($this->app->storage('cache') . '/lockout_*.cache'));
        $backups = count(glob($this->app->storage('backups') . '/backup-*.zip'));
        echo json_encode(['audit_count' => count($entries), 'lockouts' => $locks, 'backups' => $backups, 'alerts' => count(array_filter($entries, fn($e) => str_starts_with($e['action'] ?? '', 'alert.')))]);
    }

    public function api_nis2_totp() { $this->check(); $s = $this->app->totp(); echo json_encode(['secret' => $s, 'code' => $this->app->totp($s)]); }
    public function api_nis2_encrypt() { $this->check(); $d = json_decode(file_get_contents('php://input'), true); echo json_encode(['result' => $this->app->encrypt($d['data'] ?? '')]); }
    public function api_nis2_decrypt() { $this->check(); $d = json_decode(file_get_contents('php://input'), true); echo json_encode(['result' => $this->app->decrypt($d['data'] ?? '') ?? 'Failed']); }
    public function api_nis2_policy() { $this->check(); $d = json_decode(file_get_contents('php://input'), true); $e = $this->app->password_policy($d['password'] ?? ''); echo json_encode(['valid' => empty($e), 'errors' => $e]); }
    public function api_nis2_backup() { $this->check(); $d = json_decode(file_get_contents('php://input'), true); $f = $this->app->backup($d['type'] ?? 'full'); echo json_encode(['ok' => $f !== false, 'file' => $f ? basename($f) : '']); }
    public function api_nis2_audit() { $this->check(); echo json_encode(['entries' => $this->app->audit_log()]); }
    public function api_nis2_alert() { $this->check(); $d = json_decode(file_get_contents('php://input'), true); $this->app->alert($d['level'] ?? 'info', $d['msg'] ?? ''); echo json_encode(['ok' => true]); }
}
