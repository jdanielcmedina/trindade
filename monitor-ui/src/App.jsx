import { useState, useEffect, useCallback } from 'react'
import { LayoutDashboard, Activity, Database, Globe, Terminal, TrendingUp, Zap, AlertTriangle, CheckCircle2, Search, Eye, EyeOff, Clock, HardDrive, ChevronRight } from 'lucide-react'
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, AreaChart, Area, BarChart, Bar } from 'recharts'

const API = '/monitor/api'
const fetchJson = (u, o) => fetch(u, { credentials: 'same-origin', ...o }).then(r => r.ok ? r.json() : null).catch(() => null)

const C = { indigo: '#6366f1', emerald: '#10b981', blue: '#3b82f6', amber: '#f59e0b', red: '#ef4444', zinc: '#71717a', sky: '#0ea5e9', violet: '#8b5cf6', rose: '#f43f5e' }

export default function App() {
  const [auth, setAuth] = useState(false)
  const [user, setUser] = useState('')
  const [pass, setPass] = useState('')
  const [err, setErr] = useState('')
  const [showPass, setShowPass] = useState(false)

  const login = async e => {
    e.preventDefault()
    const r = await fetchJson(API + '/login', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'user=' + encodeURIComponent(user) + '&password=' + encodeURIComponent(pass) })
    r?.ok ? setAuth(true) : setErr(r?.error || 'Invalid credentials')
  }

  if (!auth) return (
    <div className="min-h-screen bg-[#09090b] flex items-center justify-center p-4">
      <div className="w-full max-w-[340px]">
        <div className="text-center mb-8">
          <div className="w-10 h-10 rounded-xl bg-indigo-500 inline-flex items-center justify-center mb-4 shadow-lg shadow-indigo-500/20"><Zap className="w-5 h-5 text-white" fill="white" /></div>
          <h1 className="text-lg font-semibold tracking-tight text-zinc-100">Trindade Monitor</h1>
          <p className="text-sm text-zinc-500 mt-1.5">Sign in to your dashboard</p>
        </div>
        <form onSubmit={login} className="bg-zinc-900 border border-zinc-800/80 rounded-xl p-6 space-y-3.5">
          <div><label className="text-[11px] font-medium text-zinc-500 uppercase tracking-wider mb-1.5 block">Username</label><input value={user} onChange={e => setUser(e.target.value)} autoFocus className="w-full bg-zinc-950 border border-zinc-800 rounded-lg px-3.5 py-2 text-sm outline-none focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/30 transition-all text-zinc-200 placeholder:text-zinc-600" /></div>
          <div><label className="text-[11px] font-medium text-zinc-500 uppercase tracking-wider mb-1.5 block">Password</label><div className="relative"><input type={showPass ? 'text' : 'password'} value={pass} onChange={e => setPass(e.target.value)} className="w-full bg-zinc-950 border border-zinc-800 rounded-lg px-3.5 py-2 text-sm outline-none focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/30 transition-all text-zinc-200 placeholder:text-zinc-600 pr-10" /><button type="button" onClick={() => setShowPass(!showPass)} className="absolute right-3 top-2 text-zinc-500 hover:text-zinc-300">{showPass ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}</button></div></div>
          {err && <p className="text-xs text-red-400 bg-red-500/5 border border-red-500/10 rounded-lg px-3 py-2">{err}</p>}
          <button type="submit" className="w-full bg-white text-black rounded-lg py-2.5 text-sm font-semibold hover:bg-zinc-100 transition-colors">Sign in</button>
        </form>
      </div>
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

  const filtered = logFilter ? logs.filter(l => l.toLowerCase().includes(logFilter.toLowerCase())) : logs.slice(-100)

  const nav = [
    { id: 'overview', icon: LayoutDashboard, label: 'Overview' },
    { id: 'performance', icon: TrendingUp, label: 'Performance' },
    { id: 'logs', icon: Terminal, label: 'Logs', count: logs.length },
    { id: 'database', icon: Database, label: 'Database' },
    { id: 'requests', icon: Globe, label: 'Requests', count: requests.length },
  ]

  return (
    <div className="flex h-screen bg-[#09090b] overflow-hidden">
      <aside className="w-[220px] border-r border-zinc-800/60 bg-[#0a0a0b] flex flex-col shrink-0">
        <div className="flex items-center gap-2.5 px-4 h-12 border-b border-zinc-800/60 shrink-0">
          <div className="w-6 h-6 rounded-md bg-indigo-500/90 flex items-center justify-center shadow-sm shadow-indigo-500/10"><Zap className="w-3.5 h-3.5 text-white" fill="white" /></div>
          <span className="text-[13px] font-semibold tracking-tight text-zinc-200">Trindade</span>
          <span className="text-[9px] text-zinc-600 font-semibold ml-auto tracking-wider">MONITOR</span>
        </div>
        <nav className="flex-1 px-2 py-3 space-y-px overflow-y-auto">
          {nav.map(n => (
            <button key={n.id} onClick={() => setTab(n.id)}
              className={`w-full flex items-center gap-2.5 px-2.5 py-1.5 rounded-md text-[13px] transition-all duration-100
                ${tab === n.id ? 'bg-zinc-800/60 text-zinc-100 font-medium' : 'text-zinc-500 hover:text-zinc-300 hover:bg-zinc-800/30'}`}>
              <n.icon className={`w-[16px] h-[16px] shrink-0 ${tab === n.id ? 'text-indigo-400' : ''}`} strokeWidth={1.5} />
              <span className="truncate">{n.label}</span>
              {n.count !== undefined && <span className={`ml-auto text-[10px] font-semibold px-1.5 py-0.5 rounded-md ${n.count > 0 ? 'bg-amber-500/10 text-amber-400' : 'bg-zinc-800 text-zinc-600'}`}>{n.count}</span>}
            </button>
          ))}
        </nav>
        <div className="px-4 py-3 border-t border-zinc-800/60">
          <div className="flex items-center gap-2 text-[11px] text-zinc-600">
            <span className="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse" /> PHP {stats?.php || '—'}
          </div>
        </div>
      </aside>

      <main className="flex-1 overflow-y-auto">
        <header className="sticky top-0 z-10 bg-[#0a0a0b]/90 backdrop-blur-md border-b border-zinc-800/60 h-11 flex items-center px-6">
          <span className="text-[11px] text-zinc-500 uppercase tracking-wider font-semibold">{tab}</span>
          <div className="ml-auto flex items-center gap-3 text-[11px] text-zinc-600">
            <span className="flex items-center gap-1.5"><span className="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse" /> Live</span>
            <span>Updated {stats?.ts || '—'}</span>
          </div>
        </header>

        <div className="p-6 max-w-[1200px]">
          <div className="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3 mb-6">
            {[{icon:Activity,label:'PHP',value:stats?.php,color:C.indigo},{icon:HardDrive,label:'Memory',value:(stats?.memory||0)+' MB',color:C.emerald},{icon:Globe,label:'Routes',value:stats?.routes,color:C.blue},{icon:Database,label:'Queue',value:stats?.queue?.pending||0,sub:'pending',color:(stats?.queue?.pending||0)>0?C.amber:C.emerald},{icon:AlertTriangle,label:'Errors',value:stats?.errors||0,color:(stats?.errors||0)>0?C.red:C.emerald},{icon:CheckCircle2,label:'Warnings',value:stats?.warnings||0,color:(stats?.warnings||0)>0?C.amber:C.emerald}].map((c,i) => (
              <div key={i} className="bg-zinc-900/60 border border-zinc-800/60 rounded-xl p-3.5 hover:border-zinc-700/60 transition-colors">
                <div className="flex items-center justify-between mb-1"><span className="text-[10px] font-semibold uppercase tracking-wider text-zinc-500">{c.label}</span><c.icon className="w-3.5 h-3.5" style={{color:c.color}} /></div>
                <div className="text-lg font-bold tracking-tight text-zinc-100">{c.value ?? '—'}</div>
                {c.sub && <div className="text-[10px] text-zinc-600 mt-0.5">{c.sub}</div>}
              </div>
            ))}
          </div>

          {tab === 'overview' && (
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
              <Panel title="Memory Usage" icon={TrendingUp} color={C.indigo}>
                <div className="h-52"><ResponsiveContainer><AreaChart data={history}><defs><linearGradient id="mem" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stopColor={C.indigo} stopOpacity={0.2} /><stop offset="100%" stopColor={C.indigo} stopOpacity={0} /></linearGradient></defs><CartesianGrid stroke="#1c1c1f" strokeDasharray="3 3" /><XAxis dataKey="time" tick={{fontSize:10,fill:'#52525b'}} interval={9} axisLine={false} tickLine={false} /><YAxis tick={{fontSize:10,fill:'#52525b'}} axisLine={false} tickLine={false} width={40} /><Tooltip contentStyle={{background:'#18181b',border:'1px solid #27272a',borderRadius:8,fontSize:12,color:'#e4e4e7',boxShadow:'0 4px 12px rgba(0,0,0,0.3)'}} /><Area type="monotone" dataKey="mem" stroke={C.indigo} fill="url(#mem)" strokeWidth={2} dot={false} /></AreaChart></ResponsiveContainer></div>
              </Panel>
              <Panel title="Errors" icon={AlertTriangle} color={C.red}>
                <div className="h-52"><ResponsiveContainer><LineChart data={history}><CartesianGrid stroke="#1c1c1f" strokeDasharray="3 3" /><XAxis dataKey="time" tick={{fontSize:10,fill:'#52525b'}} interval={9} axisLine={false} tickLine={false} /><YAxis tick={{fontSize:10,fill:'#52525b'}} axisLine={false} tickLine={false} width={40} /><Tooltip contentStyle={{background:'#18181b',border:'1px solid #27272a',borderRadius:8,fontSize:12,color:'#e4e4e7',boxShadow:'0 4px 12px rgba(0,0,0,0.3)'}} /><Line type="monotone" dataKey="errors" stroke={C.red} strokeWidth={2} dot={false} /><Line type="monotone" dataKey="routes" stroke={C.blue} strokeWidth={2} dot={false} /></LineChart></ResponsiveContainer></div>
              </Panel>
              <div className="lg:col-span-2"><Panel title="Recent Logs" icon={Terminal} color={C.zinc}>
                <div className="space-y-px max-h-80 overflow-y-auto">{filtered.slice(0,60).map((l,i) => <LogRow key={i} text={l} />)}</div>
              </Panel></div>
            </div>
          )}

          {tab === 'logs' && (
            <Panel title="Logs" icon={Terminal} color={C.zinc}>
              <div className="flex items-center gap-3 mb-4">
                <div className="relative flex-1 max-w-[280px]"><Search className="w-3.5 h-3.5 absolute left-3 top-2 text-zinc-500" /><input value={logFilter} onChange={e => setLogFilter(e.target.value)} placeholder="Filter logs..." className="w-full bg-zinc-950 border border-zinc-800 rounded-lg pl-9 pr-3 py-1.5 text-xs outline-none focus:border-indigo-500/50 transition-colors text-zinc-300 placeholder:text-zinc-600" /></div>
                <span className="text-[11px] text-zinc-600">{filtered.length} entries</span>
              </div>
              <div className="space-y-px max-h-[70vh] overflow-y-auto">{filtered.map((l,i) => <LogRow key={i} text={l} />)}</div>
            </Panel>
          )}

          {tab === 'performance' && (
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
              <Panel title="Memory Timeline" icon={TrendingUp} color={C.emerald}><div className="h-64"><ResponsiveContainer><AreaChart data={history}><defs><linearGradient id="mem2" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stopColor={C.emerald} stopOpacity={0.2} /><stop offset="100%" stopColor={C.emerald} stopOpacity={0} /></linearGradient></defs><CartesianGrid stroke="#1c1c1f" strokeDasharray="3 3" /><XAxis dataKey="time" tick={{fontSize:10,fill:'#52525b'}} interval={9} axisLine={false} tickLine={false} /><YAxis tick={{fontSize:10,fill:'#52525b'}} axisLine={false} tickLine={false} width={40} /><Tooltip contentStyle={{background:'#18181b',border:'1px solid #27272a',borderRadius:8,fontSize:12,color:'#e4e4e7',boxShadow:'0 4px 12px rgba(0,0,0,0.3)'}} /><Area type="monotone" dataKey="mem" stroke={C.emerald} fill="url(#mem2)" strokeWidth={2} dot={false} /></AreaChart></ResponsiveContainer></div></Panel>
              <Panel title="Error Rate" icon={AlertTriangle} color={C.red}><div className="h-64"><ResponsiveContainer><BarChart data={history}><CartesianGrid stroke="#1c1c1f" strokeDasharray="3 3" /><XAxis dataKey="time" tick={{fontSize:10,fill:'#52525b'}} interval={9} axisLine={false} tickLine={false} /><YAxis tick={{fontSize:10,fill:'#52525b'}} axisLine={false} tickLine={false} width={40} /><Tooltip contentStyle={{background:'#18181b',border:'1px solid #27272a',borderRadius:8,fontSize:12,color:'#e4e4e7',boxShadow:'0 4px 12px rgba(0,0,0,0.3)'}} /><Bar dataKey="errors" fill={C.red} radius={[3,3,0,0]} maxBarSize={32} /></BarChart></ResponsiveContainer></div></Panel>
              <Panel title="Database Connections" icon={Database} color={C.amber}>{stats?.dbs && Object.entries(stats.dbs).map(([n,s]) => <div key={n} className="flex items-center justify-between py-2.5 px-3 bg-zinc-950/50 rounded-lg mb-2"><span className="text-[13px] font-mono text-zinc-300">{n}</span><span className={`text-[11px] px-2 py-0.5 rounded-full font-semibold ${s==='connected'?'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20':'bg-red-500/10 text-red-400 border border-red-500/20'}`}>{s}</span></div>)}</Panel>
              <Panel title="Queue" icon={HardDrive} color={C.blue}><div className="flex items-center gap-10"><div><div className="text-xl font-bold text-amber-400">{stats?.queue?.pending||0}</div><div className="text-[11px] text-zinc-500 mt-0.5">Pending</div></div><div><div className="text-xl font-bold text-red-400">{stats?.queue?.failed||0}</div><div className="text-[11px] text-zinc-500 mt-0.5">Failed</div></div></div></Panel>
            </div>
          )}

          {tab === 'database' && (
            <Panel title="Database" icon={Database} color={C.amber}>
              <div className="space-y-2 max-w-lg">{stats?.dbs ? Object.entries(stats.dbs).map(([n,s]) => <div key={n} className="flex items-center justify-between py-3 px-4 bg-zinc-950/50 rounded-xl border border-zinc-800/60"><div className="flex items-center gap-2.5"><Database className="w-4 h-4 text-amber-400/70" /><span className="text-sm font-mono text-zinc-300 font-medium">{n}</span></div><span className={`text-[11px] px-2.5 py-1 rounded-full font-semibold ${s==='connected'?'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20':'bg-red-500/10 text-red-400 border border-red-500/20'}`}>{s==='connected'?'Connected':'Error'}</span></div>) : <p className="text-sm text-zinc-600">No database connections configured.</p>}</div>
            </Panel>
          )}

          {tab === 'requests' && (
            <div className="bg-zinc-900/60 border border-zinc-800/60 rounded-xl overflow-hidden">
              <div className="flex items-center gap-2 px-5 py-3 border-b border-zinc-800/60"><Globe className="w-4 h-4 text-indigo-400" /><span className="text-[13px] font-semibold text-zinc-200">Requests</span><span className="text-[11px] text-zinc-600 ml-2">{requests.length} entries</span></div>
              <table className="w-full text-[13px]"><thead><tr className="bg-zinc-950/30 text-zinc-500 border-b border-zinc-800/40">{['Time','Action','IP','User'].map(h=><th key={h} className="text-left font-medium px-4 py-2.5 text-[11px] uppercase tracking-wider">{h}</th>)}</tr></thead><tbody>{requests.map((r,i)=><tr key={i} className="border-b border-zinc-800/20 hover:bg-zinc-800/20 transition-colors"><td className="px-4 py-2.5 font-mono text-zinc-500 text-xs">{(r.ts||'').substring(11,19)}</td><td className="px-4 py-2.5 text-indigo-400/90 font-medium">{r.action}</td><td className="px-4 py-2.5 font-mono text-zinc-500 text-xs">{r.ip}</td><td className="px-4 py-2.5 text-zinc-400 text-xs">{r.user||'—'}</td></tr>)}</tbody></table>
            </div>
          )}
        </div>
      </main>
    </div>
  )
}

function Panel({ title, icon: Icon, color, children }) {
  return <div className="bg-zinc-900/60 border border-zinc-800/60 rounded-xl p-5"><div className="flex items-center gap-2 mb-4"><Icon className="w-4 h-4" style={{color}} /><h3 className="text-[13px] font-semibold text-zinc-200">{title}</h3></div>{children}</div>
}

function LogRow({ text }) {
  let color = 'text-zinc-500', bg = '', level = ''
  if (text.includes('[error]')) { color = 'text-red-400/90'; bg = 'hover:bg-red-500/[0.04]'; level = 'ERR' }
  else if (text.includes('[warning]')) { color = 'text-amber-400/90'; bg = 'hover:bg-amber-500/[0.04]'; level = 'WRN' }
  else if (text.includes('[info]')) { color = 'text-zinc-400'; bg = 'hover:bg-zinc-800/30'; level = 'INF' }
  else if (text.includes('[debug]')) { color = 'text-zinc-600'; level = 'DBG' }
  return <div className={`text-[12px] font-mono py-1 px-2 rounded-md flex items-start gap-2.5 ${bg} transition-colors`}><span className="text-[10px] font-bold text-zinc-600 w-7 shrink-0 text-right mt-px">{level}</span><span className={`${color} truncate`}>{text}</span></div>
}
