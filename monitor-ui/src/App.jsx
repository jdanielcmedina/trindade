import { useState, useEffect, useCallback } from 'react'
import { Activity, Cpu, Database, Globe, HardDrive, Terminal, TrendingUp, Zap, AlertTriangle, CheckCircle2, Clock, ChevronRight, ChevronDown, Search, Settings, Layers, BarChart3, ListTree, Server, Shield, LogOut, Eye, EyeOff } from 'lucide-react'
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, AreaChart, Area, BarChart, Bar } from 'recharts'
import * as Tabs from '@radix-ui/react-tabs'
import * as TooltipPrimitive from '@radix-ui/react-tooltip'
import * as ScrollArea from '@radix-ui/react-scroll-area'

const HOST = window.location.origin
const API = '/monitor/api'

async function fetchJson(url, opts) {
  try { const r = await fetch(url, { credentials: 'same-origin', ...opts }); if (!r.ok) return null; return r.json() } catch { return null }
}

const colors = { indigo: '#818cf8', emerald: '#34d399', blue: '#60a5fa', amber: '#fbbf24', red: '#f87171', zinc: '#a1a1aa', sky: '#38bdf8', violet: '#a78bfa', rose: '#fb7185' }

function StatCard({ icon: Icon, label, value, sub, color, trend }) {
  return (
    <div className="bg-zinc-900/70 border border-zinc-800/80 rounded-xl p-4 backdrop-blur-sm hover:border-zinc-700/80 transition-all">
      <div className="flex items-center justify-between mb-1.5">
        <span className="text-[11px] font-medium uppercase tracking-wider text-zinc-500">{label}</span>
        <TooltipPrimitive.Root delayDuration={300}>
          <TooltipPrimitive.Trigger asChild><Icon className="w-3.5 h-3.5" style={{ color }} /></TooltipPrimitive.Trigger>
          <TooltipPrimitive.Portal><TooltipPrimitive.Content side="top" className="bg-zinc-800 text-xs text-zinc-300 px-2 py-1 rounded-md border border-zinc-700">{label}</TooltipPrimitive.Content></TooltipPrimitive.Portal>
        </TooltipPrimitive.Root>
      </div>
      <div className="text-xl font-bold tracking-tight" style={{ color }}>{value ?? '—'}</div>
      {sub && <div className="text-[11px] text-zinc-600 mt-0.5">{sub}</div>}
      {trend && <div className={`text-[10px] mt-1 ${trend > 0 ? 'text-emerald-400' : 'text-red-400'}`}>{trend > 0 ? '+' : ''}{trend}%</div>}
    </div>
  )
}

function NavItem({ icon: Icon, label, active, onClick, badge }) {
  return (
    <button onClick={onClick} className={`w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-[13px] transition-all duration-150 group ${active ? 'bg-indigo-500/10 text-indigo-400 font-medium' : 'text-zinc-400 hover:text-zinc-200 hover:bg-zinc-800/50'}`}>
      <Icon className={`w-[18px] h-[18px] shrink-0 ${active ? '' : 'opacity-60 group-hover:opacity-100'}`} />
      <span className="truncate">{label}</span>
      {badge !== undefined && <span className={`ml-auto text-[10px] font-bold px-1.5 py-0.5 rounded-full ${badge > 0 ? 'bg-amber-500/10 text-amber-400' : 'bg-zinc-800 text-zinc-500'}`}>{badge}</span>}
    </button>
  )
}

function LogLine({ text }) {
  let color = 'text-zinc-500'
  let bg = ''
  if (text.includes('[error]')) { color = 'text-red-400'; bg = 'hover:bg-red-500/5' }
  else if (text.includes('[warning]')) { color = 'text-amber-400'; bg = 'hover:bg-amber-500/5' }
  else if (text.includes('[info]')) { color = 'text-zinc-400'; bg = 'hover:bg-zinc-800/30' }
  else if (text.includes('[debug]')) { color = 'text-zinc-600'; bg = '' }
  return <div className={`text-xs font-mono py-0.5 px-2 rounded ${color} ${bg} transition-colors truncate`}>{text}</div>
}

export default function App() {
  const [auth, setAuth] = useState(false)
  const [user, setUser] = useState('')
  const [pass, setPass] = useState('')
  const [err, setErr] = useState('')
  const [showPass, setShowPass] = useState(false)

  const login = async (e) => {
    e.preventDefault()
    const r = await fetchJson(API + '/login', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'user=' + encodeURIComponent(user) + '&password=' + encodeURIComponent(pass) })
    if (r?.ok) setAuth(true)
    else setErr(r?.error || 'Credenciais invalidas')
  }

  if (!auth) return (
    <div className="min-h-screen bg-zinc-950 flex items-center justify-center">
      <form onSubmit={login} className="w-full max-w-sm bg-zinc-900 border border-zinc-800 rounded-2xl p-8">
        <div className="text-center mb-6">
          <div className="w-10 h-10 rounded-xl bg-indigo-500 inline-flex items-center justify-center mb-3">
            <Zap className="w-5 h-5 text-white" fill="white" />
          </div>
          <h1 className="text-lg font-semibold tracking-tight">Trindade Monitor</h1>
          <p className="text-sm text-zinc-500 mt-1">Sign in to your dashboard</p>
        </div>
        <div className="space-y-3">
          <input value={user} onChange={e => setUser(e.target.value)} placeholder="Username" autoFocus className="w-full bg-zinc-950 border border-zinc-800 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-indigo-500 transition-colors placeholder:text-zinc-600" />
          <div className="relative">
            <input type={showPass ? 'text' : 'password'} value={pass} onChange={e => setPass(e.target.value)} placeholder="Password" className="w-full bg-zinc-950 border border-zinc-800 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-indigo-500 transition-colors placeholder:text-zinc-600" />
            <button type="button" onClick={() => setShowPass(!showPass)} className="absolute right-3 top-2.5 text-zinc-500 hover:text-zinc-300">{showPass ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}</button>
          </div>
          {err && <p className="text-xs text-red-400">{err}</p>}
          <button type="submit" className="w-full bg-white text-black rounded-lg py-2.5 text-sm font-semibold hover:bg-zinc-200 transition-colors">Sign in</button>
        </div>
      </form>
    </div>
  )

  return <Dashboard />
}

function Dashboard() {
  const [stats, setStats] = useState(null)
  const [logs, setLogs] = useState([])
  const [requests, setRequests] = useState([])
  const [history, setHistory] = useState([])
  const [tab, setTab] = useState('overview')
  const [logFilter, setLogFilter] = useState('')

  const load = useCallback(async () => {
    const s = await fetchJson(API + '/stats')
    if (s) { setStats(s); setHistory(prev => [...prev.slice(-59), { time: s.ts, mem: s.memory, errors: s.errors, routes: s.routes }]) }
    const l = await fetchJson(API + '/logs')
    if (l) setLogs(l.split('\n').filter(Boolean))
    const r = await fetchJson(API + '/requests')
    if (r) setRequests(r)
  }, [])

  useEffect(() => { load(); const i = setInterval(load, 3000); return () => clearInterval(i) }, [load])

  const filteredLogs = logFilter ? logs.filter(l => l.toLowerCase().includes(logFilter.toLowerCase())) : logs.slice(-100)
  const errors = stats?.errors || 0
  const warnings = stats?.warnings || 0

  const nav = [
    { id: 'overview', icon: Activity, label: 'Overview' },
    { id: 'performance', icon: TrendingUp, label: 'Performance' },
    { id: 'logs', icon: Terminal, label: 'Logs', badge: logs.length },
    { id: 'database', icon: Database, label: 'Database' },
    { id: 'requests', icon: Globe, label: 'Requests', badge: requests.length },
  ]

  return (
    <TooltipPrimitive.Provider>
      <div className="flex h-screen bg-zinc-950 overflow-hidden">
        {/* Sidebar */}
        <aside className="w-60 border-r border-zinc-800 bg-zinc-950 flex flex-col shrink-0">
          <div className="flex items-center gap-2.5 px-4 h-14 border-b border-zinc-800 shrink-0">
            <div className="w-7 h-7 rounded-lg bg-indigo-500 flex items-center justify-center"><Zap className="w-4 h-4 text-white" fill="white" /></div>
            <span className="text-sm font-semibold tracking-tight">Trindade</span>
            <span className="text-[10px] text-zinc-500 font-medium ml-auto bg-zinc-900 px-1.5 py-0.5 rounded">MONITOR</span>
          </div>
          <ScrollArea.Root className="flex-1 overflow-hidden">
            <ScrollArea.Viewport className="h-full">
              <nav className="px-2 py-3 space-y-0.5">
                {nav.map(n => <NavItem key={n.id} icon={n.icon} label={n.label} active={tab === n.id} onClick={() => setTab(n.id)} badge={n.badge} />)}
              </nav>
            </ScrollArea.Viewport>
            <ScrollArea.Scrollbar className="flex select-none touch-none p-0.5 bg-zinc-900 transition-colors w-2" orientation="vertical">
              <ScrollArea.Thumb className="flex-1 bg-zinc-700 rounded-full relative" />
            </ScrollArea.Scrollbar>
          </ScrollArea.Root>
          <div className="px-3 py-3 border-t border-zinc-800">
            <div className="flex items-center gap-2 text-xs text-zinc-500">
              <span className="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse" /> PHP {stats?.php || '—'}
            </div>
          </div>
        </aside>

        {/* Main */}
        <main className="flex-1 overflow-y-auto">
          <header className="sticky top-0 z-10 bg-zinc-950/80 backdrop-blur-md border-b border-zinc-800 px-6 h-12 flex items-center gap-4">
            <span className="text-xs text-zinc-500 uppercase tracking-wider font-medium">{tab}</span>
            <div className="ml-auto flex items-center gap-4 text-xs text-zinc-500">
              <span className="flex items-center gap-1.5"><span className="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse" /> Live</span>
              <span>Updated {stats?.ts || '—'}</span>
            </div>
          </header>

          <div className="p-6 max-w-7xl">
            {/* Stats grid */}
            <div className="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3 mb-6">
              <StatCard icon={Server} label="PHP" value={stats?.php} color={colors.indigo} />
              <StatCard icon={Cpu} label="Memory" value={stats?.memory + ' MB'} color={colors.emerald} />
              <StatCard icon={Globe} label="Routes" value={stats?.routes} color={colors.blue} />
              <StatCard icon={Database} label="Queue" value={stats?.queue?.pending || 0} sub="pending" color={stats?.queue?.pending ? colors.amber : colors.emerald} />
              <StatCard icon={AlertTriangle} label="Errors" value={errors} color={errors ? colors.red : colors.emerald} />
              <StatCard icon={CheckCircle2} label="Warnings" value={warnings} color={warnings ? colors.amber : colors.emerald} />
            </div>

            {tab === 'overview' && (
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div className="bg-zinc-900/70 border border-zinc-800/80 rounded-xl p-5 backdrop-blur-sm">
                  <h3 className="text-sm font-semibold mb-4 flex items-center gap-2"><BarChart3 className="w-4 h-4 text-indigo-400" /> Memory Usage</h3>
                  <div className="h-52"><ResponsiveContainer><AreaChart data={history}><defs><linearGradient id="mem" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stopColor="#818cf8" stopOpacity={0.25} /><stop offset="100%" stopColor="#818cf8" stopOpacity={0} /></linearGradient></defs><CartesianGrid strokeDasharray="3 3" stroke="#27272a" /><XAxis dataKey="time" tick={{fontSize:10,fill:'#71717a'}} interval={9} /><YAxis tick={{fontSize:10,fill:'#71717a'}} /><Tooltip contentStyle={{background:'#18181b',border:'1px solid #27272a',borderRadius:8,fontSize:12,color:'#fafafa'}} /><Area type="monotone" dataKey="mem" stroke="#818cf8" fill="url(#mem)" strokeWidth={2} /></AreaChart></ResponsiveContainer></div>
                </div>
                <div className="bg-zinc-900/70 border border-zinc-800/80 rounded-xl p-5 backdrop-blur-sm">
                  <h3 className="text-sm font-semibold mb-4 flex items-center gap-2"><Activity className="w-4 h-4 text-amber-400" /> Errors over time</h3>
                  <div className="h-52"><ResponsiveContainer><LineChart data={history}><CartesianGrid strokeDasharray="3 3" stroke="#27272a" /><XAxis dataKey="time" tick={{fontSize:10,fill:'#71717a'}} interval={9} /><YAxis tick={{fontSize:10,fill:'#71717a'}} /><Tooltip contentStyle={{background:'#18181b',border:'1px solid #27272a',borderRadius:8,fontSize:12,color:'#fafafa'}} /><Line type="monotone" dataKey="errors" stroke="#f87171" strokeWidth={2} dot={false} /><Line type="monotone" dataKey="routes" stroke="#60a5fa" strokeWidth={2} dot={false} /></LineChart></ResponsiveContainer></div>
                </div>
                <div className="lg:col-span-2 bg-zinc-900/70 border border-zinc-800/80 rounded-xl p-5 backdrop-blur-sm max-h-96 flex flex-col">
                  <div className="flex items-center justify-between mb-3"><h3 className="text-sm font-semibold flex items-center gap-2"><Terminal className="w-4 h-4 text-zinc-400" /> Recent Logs</h3></div>
                  <ScrollArea.Root className="flex-1 overflow-hidden"><ScrollArea.Viewport className="h-full"><div className="space-y-px">{filteredLogs.map((l, i) => <LogLine key={i} text={l} />)}</div></ScrollArea.Viewport><ScrollArea.Scrollbar className="flex select-none touch-none p-0.5 bg-zinc-900 w-2" orientation="vertical"><ScrollArea.Thumb className="flex-1 bg-zinc-700 rounded-full relative" /></ScrollArea.Scrollbar></ScrollArea.Root>
                </div>
              </div>
            )}

            {tab === 'logs' && (
              <div className="bg-zinc-900/70 border border-zinc-800/80 rounded-xl p-5 backdrop-blur-sm">
                <div className="flex items-center gap-3 mb-4">
                  <div className="relative flex-1 max-w-xs"><Search className="w-3.5 h-3.5 absolute left-3 top-2.5 text-zinc-500" /><input value={logFilter} onChange={e => setLogFilter(e.target.value)} placeholder="Filter logs..." className="w-full bg-zinc-950 border border-zinc-800 rounded-lg pl-9 pr-3 py-2 text-xs outline-none focus:border-indigo-500 placeholder:text-zinc-600" /></div>
                  <span className="text-xs text-zinc-500">{filteredLogs.length} entries</span>
                </div>
                <ScrollArea.Root className="h-[70vh] overflow-hidden"><ScrollArea.Viewport className="h-full"><div className="space-y-px">{filteredLogs.map((l, i) => <LogLine key={i} text={l} />)}</div></ScrollArea.Viewport><ScrollArea.Scrollbar className="flex select-none touch-none p-0.5 bg-zinc-900 w-2" orientation="vertical"><ScrollArea.Thumb className="flex-1 bg-zinc-700 rounded-full relative" /></ScrollArea.Scrollbar></ScrollArea.Root>
              </div>
            )}

            {tab === 'performance' && (
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div className="bg-zinc-900/70 border border-zinc-800/80 rounded-xl p-5 backdrop-blur-sm"><h3 className="text-sm font-semibold mb-4">Memory Timeline</h3><div className="h-64"><ResponsiveContainer><AreaChart data={history}><defs><linearGradient id="mem2" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stopColor="#34d399" stopOpacity={0.25} /><stop offset="100%" stopColor="#34d399" stopOpacity={0} /></linearGradient></defs><CartesianGrid strokeDasharray="3 3" stroke="#27272a" /><XAxis dataKey="time" tick={{fontSize:10,fill:'#71717a'}} interval={9} /><YAxis tick={{fontSize:10,fill:'#71717a'}} /><Tooltip contentStyle={{background:'#18181b',border:'1px solid #27272a',borderRadius:8,fontSize:12,color:'#fafafa'}} /><Area type="monotone" dataKey="mem" stroke="#34d399" fill="url(#mem2)" strokeWidth={2} /></AreaChart></ResponsiveContainer></div></div>
                <div className="bg-zinc-900/70 border border-zinc-800/80 rounded-xl p-5 backdrop-blur-sm"><h3 className="text-sm font-semibold mb-4">Error Rate</h3><div className="h-64"><ResponsiveContainer><BarChart data={history}><CartesianGrid strokeDasharray="3 3" stroke="#27272a" /><XAxis dataKey="time" tick={{fontSize:10,fill:'#71717a'}} interval={9} /><YAxis tick={{fontSize:10,fill:'#71717a'}} /><Tooltip contentStyle={{background:'#18181b',border:'1px solid #27272a',borderRadius:8,fontSize:12,color:'#fafafa'}} /><Bar dataKey="errors" fill="#f87171" radius={[4,4,0,0]} /></BarChart></ResponsiveContainer></div></div>
                <div className="bg-zinc-900/70 border border-zinc-800/80 rounded-xl p-5 backdrop-blur-sm"><h3 className="text-sm font-semibold mb-4 flex items-center gap-2"><Database className="w-4 h-4 text-amber-400" /> DB Connections</h3><div className="space-y-2">{stats?.dbs && Object.entries(stats.dbs).map(([n, s]) => (<div key={n} className="flex items-center justify-between py-2.5 px-3 bg-zinc-950 rounded-lg"><span className="text-sm font-mono">{n}</span><span className={`text-xs px-2 py-0.5 rounded-full font-medium ${s === 'connected' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400'}`}>{s}</span></div>))}</div></div>
                <div className="bg-zinc-900/70 border border-zinc-800/80 rounded-xl p-5 backdrop-blur-sm"><h3 className="text-sm font-semibold mb-4 flex items-center gap-2"><HardDrive className="w-4 h-4 text-blue-400" /> Queue</h3><div className="flex items-center gap-8"><div className="text-center"><div className="text-2xl font-bold text-amber-400">{stats?.queue?.pending || 0}</div><div className="text-[11px] text-zinc-500 mt-1">Pending</div></div><div className="text-center"><div className="text-2xl font-bold text-red-400">{stats?.queue?.failed || 0}</div><div className="text-[11px] text-zinc-500 mt-1">Failed</div></div></div></div>
              </div>
            )}

            {tab === 'database' && (
              <div className="bg-zinc-900/70 border border-zinc-800/80 rounded-xl p-5 backdrop-blur-sm">
                <h3 className="text-sm font-semibold mb-4 flex items-center gap-2"><Database className="w-4 h-4 text-amber-400" /> Database Connections</h3>
                <div className="space-y-2 max-w-lg">{stats?.dbs && Object.entries(stats.dbs).map(([n, s]) => (<div key={n} className="flex items-center justify-between py-3 px-4 bg-zinc-950 rounded-xl border border-zinc-800"><span className="text-sm font-mono font-medium">{n}</span><span className={`text-xs px-2.5 py-1 rounded-full font-semibold ${s === 'connected' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'}`}>{s === 'connected' ? 'Connected' : 'Error'}</span></div>))}</div>
              </div>
            )}

            {tab === 'requests' && (
              <div className="bg-zinc-900/70 border border-zinc-800/80 rounded-xl overflow-hidden backdrop-blur-sm">
                <div className="px-5 py-3 border-b border-zinc-800 text-sm font-semibold flex items-center gap-2"><Globe className="w-4 h-4 text-indigo-400" /> Recent Requests</div>
                <div className="overflow-x-auto"><table className="w-full text-xs"><thead><tr className="bg-zinc-950/50 text-zinc-500">{['Time','Action','IP','User'].map(h=><th key={h} className="text-left px-4 py-2.5 font-medium uppercase tracking-wider">{h}</th>)}</tr></thead><tbody>{requests.map((r,i)=>(<tr key={i} className="border-t border-zinc-800/50 hover:bg-zinc-800/20 transition-colors"><td className="px-4 py-2 font-mono text-zinc-500">{(r.ts||'').substring(11,19)}</td><td className="px-4 py-2 text-indigo-400 font-medium">{r.action}</td><td className="px-4 py-2 font-mono text-zinc-500">{r.ip}</td><td className="px-4 py-2 text-zinc-400">{r.user||'—'}</td></tr>))}</tbody></table></div>
              </div>
            )}
          </div>
        </main>
      </div>
    </TooltipPrimitive.Provider>
  )
}
