import { useState, useEffect, useCallback } from 'react'
import { motion, AnimatePresence } from 'framer-motion'
import { Activity, Database, Globe, Terminal, TrendingUp, Zap, AlertTriangle, CheckCircle2, HardDrive, Server } from 'lucide-react'
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, AreaChart, Area, BarChart, Bar } from 'recharts'
import Sidebar from './Sidebar'
import MetricCard from './MetricCard'
import ChartCard from './ChartCard'
import LogViewer from './LogViewer'
import RequestTable from './RequestTable'
import { getStats, getLogs, getRequests } from '../lib/api'

export default function Dashboard() {
  const [stats, setStats] = useState(null)
  const [logs, setLogs] = useState([])
  const [requests, setRequests] = useState([])
  const [history, setHistory] = useState([])
  const [tab, setTab] = useState('overview')
  const [logFilter, setLogFilter] = useState('')

  const load = useCallback(async () => {
    const s = await getStats()
    if (s) { setStats(s); setHistory(prev => [...prev.slice(-59), { time: s.ts, mem: s.memory, errors: s.errors, routes: s.routes }]) }
    const l = await getLogs()
    if (l) setLogs(l.split('\n').filter(Boolean))
    const r = await getRequests()
    if (r) setRequests(r)
  }, [])

  useEffect(() => { load(); const i = setInterval(load, 3000); return () => clearInterval(i) }, [load])

  const errs = stats?.errors || 0
  const warns = stats?.warnings || 0
  const pending = stats?.queue?.pending || 0

  const metrics = [
    { icon: Server, label: 'PHP', value: stats?.php, color: '#a1a1aa' },
    { icon: HardDrive, label: 'Memory', value: (stats?.memory || 0) + ' MB', color: '#a1a1aa' },
    { icon: Globe, label: 'Routes', value: stats?.routes, color: '#a1a1aa' },
    { icon: Database, label: 'Queue', value: pending, sub: 'pending', color: '#a1a1aa' },
    { icon: AlertTriangle, label: 'Errors', value: errs, color: errs ? '#f87171' : '#a1a1aa' },
    { icon: CheckCircle2, label: 'Warnings', value: warns, color: warns ? '#fbbf24' : '#a1a1aa' },
  ]

  return (
    <div className="flex h-screen bg-[#0a0a0b] overflow-hidden">
      <Sidebar tab={tab} setTab={setTab} stats={stats} />

      <main className="flex-1 overflow-y-auto">
        <header className="sticky top-0 z-10 bg-[#0a0a0b]/80 backdrop-blur-xl border-b border-zinc-800/40 h-10 flex items-center px-6">
          <span className="text-[10px] text-zinc-500 uppercase tracking-widest font-semibold">{tab}</span>
          <div className="ml-auto flex items-center gap-3 text-[10px] text-zinc-600">
            <span className="flex items-center gap-1.5"><span className="w-1 h-1 rounded-full bg-emerald-500/60 animate-pulse" /> Live</span>
            <span>Updated {stats?.ts || '—'}</span>
          </div>
        </header>

        <div className="p-6 max-w-[1240px]">
          <AnimatePresence mode="wait">
            <motion.div key={tab} initial={{ opacity: 0, y: 6 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -6 }} transition={{ duration: 0.15 }}>
              <div className="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-2.5 mb-6">
                {metrics.map((m, i) => <MetricCard key={i} {...m} loading={!stats} />)}
              </div>

              {tab === 'overview' && (
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                  <ChartCard title="Memory Usage" icon={TrendingUp}>
                    <div className="h-[200px] -mx-1">
                      <ResponsiveContainer>
                        <AreaChart data={history}>
                          <defs><linearGradient id="mem" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stopColor="#a1a1aa" stopOpacity={0.08} /><stop offset="100%" stopColor="#a1a1aa" stopOpacity={0} /></linearGradient></defs>
                          <CartesianGrid stroke="#18181b" strokeDasharray="3 3" vertical={false} />
                          <XAxis dataKey="time" tick={{ fontSize: 10, fill: '#52525b' }} interval={9} axisLine={false} tickLine={false} />
                          <YAxis tick={{ fontSize: 10, fill: '#52525b' }} axisLine={false} tickLine={false} width={36} />
                          <Tooltip contentStyle={{ background: '#18181b', border: '1px solid #27272a', borderRadius: 8, fontSize: 12, color: '#d4d4d8', boxShadow: '0 4px 20px rgba(0,0,0,0.5)' }} />
                          <Area type="monotone" dataKey="mem" stroke="#a1a1aa" fill="url(#mem)" strokeWidth={1.5} dot={false} />
                        </AreaChart>
                      </ResponsiveContainer>
                    </div>
                  </ChartCard>

                  <ChartCard title="Error Rate" icon={AlertTriangle}>
                    <div className="h-[200px] -mx-1">
                      <ResponsiveContainer>
                        <LineChart data={history}>
                          <CartesianGrid stroke="#18181b" strokeDasharray="3 3" vertical={false} />
                          <XAxis dataKey="time" tick={{ fontSize: 10, fill: '#52525b' }} interval={9} axisLine={false} tickLine={false} />
                          <YAxis tick={{ fontSize: 10, fill: '#52525b' }} axisLine={false} tickLine={false} width={36} />
                          <Tooltip contentStyle={{ background: '#18181b', border: '1px solid #27272a', borderRadius: 8, fontSize: 12, color: '#d4d4d8', boxShadow: '0 4px 20px rgba(0,0,0,0.5)' }} />
                          <Line type="monotone" dataKey="errors" stroke="#f87171" strokeWidth={1.5} dot={false} />
                          <Line type="monotone" dataKey="routes" stroke="#52525b" strokeWidth={1} dot={false} strokeDasharray="4 4" />
                        </LineChart>
                      </ResponsiveContainer>
                    </div>
                  </ChartCard>

                  <div className="lg:col-span-2">
                    <LogViewer logs={logs} filter={logFilter} setFilter={setLogFilter} />
                  </div>
                </div>
              )}

              {tab === 'performance' && (
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                  <ChartCard title="Memory Timeline" icon={TrendingUp}>
                    <div className="h-[240px]">
                      <ResponsiveContainer>
                        <AreaChart data={history}>
                          <defs><linearGradient id="mem2" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stopColor="#a1a1aa" stopOpacity={0.08} /><stop offset="100%" stopColor="#a1a1aa" stopOpacity={0} /></linearGradient></defs>
                          <CartesianGrid stroke="#18181b" strokeDasharray="3 3" vertical={false} />
                          <XAxis dataKey="time" tick={{ fontSize: 10, fill: '#52525b' }} interval={9} axisLine={false} tickLine={false} />
                          <YAxis tick={{ fontSize: 10, fill: '#52525b' }} axisLine={false} tickLine={false} width={36} />
                          <Tooltip contentStyle={{ background: '#18181b', border: '1px solid #27272a', borderRadius: 8, fontSize: 12, color: '#d4d4d8', boxShadow: '0 4px 20px rgba(0,0,0,0.5)' }} />
                          <Area type="monotone" dataKey="mem" stroke="#a1a1aa" fill="url(#mem2)" strokeWidth={1.5} dot={false} />
                        </AreaChart>
                      </ResponsiveContainer>
                    </div>
                  </ChartCard>

                  <ChartCard title="Error Distribution" icon={Activity}>
                    <div className="h-[240px]">
                      <ResponsiveContainer>
                        <BarChart data={history}>
                          <CartesianGrid stroke="#18181b" strokeDasharray="3 3" vertical={false} />
                          <XAxis dataKey="time" tick={{ fontSize: 10, fill: '#52525b' }} interval={9} axisLine={false} tickLine={false} />
                          <YAxis tick={{ fontSize: 10, fill: '#52525b' }} axisLine={false} tickLine={false} width={36} />
                          <Tooltip contentStyle={{ background: '#18181b', border: '1px solid #27272a', borderRadius: 8, fontSize: 12, color: '#d4d4d8', boxShadow: '0 4px 20px rgba(0,0,0,0.5)' }} />
                          <Bar dataKey="errors" fill="#f87171" radius={[2, 2, 0, 0]} maxBarSize={28} />
                        </BarChart>
                      </ResponsiveContainer>
                    </div>
                  </ChartCard>

                  <ChartCard title="DB Connections" icon={Database}>
                    <div className="space-y-2">
                      {stats?.dbs && Object.entries(stats.dbs).map(([n, s]) => (
                        <div key={n} className="flex items-center justify-between py-2.5 px-3 bg-black/30 rounded-lg border border-zinc-800/30">
                          <span className="text-[12px] font-mono text-zinc-400">{n}</span>
                          <span className={`text-[10px] px-2 py-0.5 rounded-full font-semibold ${s === 'connected' ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'}`}>{s === 'connected' ? 'Connected' : 'Error'}</span>
                        </div>
                      ))}
                    </div>
                  </ChartCard>

                  <ChartCard title="Queue" icon={HardDrive}>
                    <div className="flex items-center gap-12">
                      <div><div className="text-xl font-bold text-zinc-200 tabular-nums">{pending}</div><div className="text-[10px] text-zinc-500 mt-0.5">Pending</div></div>
                      <div><div className="text-xl font-bold text-zinc-200 tabular-nums">{stats?.queue?.failed || 0}</div><div className="text-[10px] text-zinc-500 mt-0.5">Failed</div></div>
                    </div>
                  </ChartCard>
                </div>
              )}

              {tab === 'logs' && <LogViewer logs={logs} filter={logFilter} setFilter={setLogFilter} />}
              {tab === 'database' && (
                <ChartCard title="Database Connections" icon={Database}>
                  <div className="space-y-2 max-w-lg">
                    {stats?.dbs ? Object.entries(stats.dbs).map(([n, s]) => (
                      <div key={n} className="flex items-center justify-between py-3 px-4 bg-black/30 rounded-xl border border-zinc-800/30">
                        <div className="flex items-center gap-2.5"><Database className="w-4 h-4 text-zinc-500" /><span className="text-[13px] font-mono text-zinc-300 font-medium">{n}</span></div>
                        <span className={`text-[10px] px-2.5 py-1 rounded-full font-semibold ${s === 'connected' ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'}`}>{s === 'connected' ? 'Connected' : 'Error'}</span>
                      </div>
                    )) : <p className="text-sm text-zinc-600">No database connections configured.</p>}
                  </div>
                </ChartCard>
              )}
              {tab === 'requests' && <RequestTable requests={requests} />}
            </motion.div>
          </AnimatePresence>
        </div>
      </main>
    </div>
  )
}
