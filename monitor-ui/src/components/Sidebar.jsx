import { motion } from 'framer-motion'
import { LayoutDashboard, Activity, Database, Globe, Terminal, TrendingUp, Zap, HardDrive } from 'lucide-react'

const items = [
  { id: 'overview', icon: LayoutDashboard, label: 'Overview' },
  { id: 'performance', icon: Activity, label: 'Performance' },
  { id: 'logs', icon: Terminal, label: 'Logs' },
  { id: 'database', icon: Database, label: 'Database' },
  { id: 'requests', icon: Globe, label: 'Requests' },
]

export default function Sidebar({ tab, setTab, stats }) {
  return (
    <aside className="w-[220px] border-r border-zinc-800/40 bg-[#08080a] flex flex-col shrink-0 select-none">
      <div className="flex items-center gap-2 px-4 h-11 border-b border-zinc-800/40 shrink-0">
        <div className="w-6 h-6 rounded-md bg-white/10 flex items-center justify-center ring-1 ring-white/5">
          <Zap className="w-3.5 h-3.5 text-white" fill="currentColor" />
        </div>
        <span className="text-[13px] font-semibold tracking-tight text-zinc-200">Trindade</span>
        <span className="text-[9px] text-zinc-600 font-semibold ml-auto tracking-widest">MONITOR</span>
      </div>

      <nav className="flex-1 px-2.5 py-3 space-y-0.5 overflow-y-auto">
        {items.map(item => (
          <button key={item.id} onClick={() => setTab(item.id)}
            className={`w-full flex items-center gap-2.5 px-2.5 py-1.5 rounded-md text-[12.5px] transition-all duration-75 relative group
              ${tab === item.id ? 'text-zinc-100' : 'text-zinc-500 hover:text-zinc-300'}`}>
            {tab === item.id && (
              <motion.div layoutId="sidebar-active" className="absolute inset-0 bg-zinc-800/50 rounded-md ring-1 ring-zinc-700/40" transition={{ type: 'spring', bounce: 0.2, duration: 0.3 }} />
            )}
            <item.icon className={`w-[15px] h-[15px] shrink-0 relative z-10 ${tab === item.id ? 'text-zinc-200' : ''}`} strokeWidth={1.5} />
            <span className="truncate relative z-10">{item.label}</span>
          </button>
        ))}
      </nav>

      <div className="px-4 py-3 border-t border-zinc-800/40">
        <div className="flex items-center gap-2 text-[11px] text-zinc-600">
          <span className="w-1.5 h-1.5 rounded-full bg-emerald-500/60 animate-pulse" />
          PHP {stats?.php || '—'}
        </div>
      </div>
    </aside>
  )
}
