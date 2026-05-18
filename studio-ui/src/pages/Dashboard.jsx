import { useEffect, useState } from 'react'
import { api } from '../lib/api'

export default function Dashboard() {
  const [stats, setStats] = useState(null)
  useEffect(() => { api.stats().then(setStats) }, [])

  return (
    <div>
      <div className="mb-8">
        <h1 className="text-lg font-semibold tracking-tight">Dashboard</h1>
        <p className="text-sm text-[var(--text3)] mt-1">Overview of your Trindade instance</p>
      </div>

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {[
          { label: 'PHP Version', value: stats?.php, icon: '{}', color: 'var(--accent)' },
          { label: 'Routes', value: stats?.routes, icon: '//', color: 'var(--green)' },
          { label: 'DB Tables', value: stats?.tables, icon: 'DB', color: 'var(--blue)' },
          { label: 'Storage', value: stats?.storage, icon: 'HD', color: 'var(--amber)' },
        ].map(c => (
          <div key={c.label}
            className="group bg-[var(--surface)] border border-[var(--border)] rounded-xl p-5 hover:border-[var(--border-hover)] transition-all duration-200"
          >
            <div className="flex items-center justify-between mb-3">
              <span className="text-[11px] font-medium uppercase tracking-widest text-[var(--text3)]">{c.label}</span>
              <span className="text-[11px] font-mono font-medium tracking-wider" style={{ color: c.color }}>{c.icon}</span>
            </div>
            <div className="text-2xl font-bold tracking-tight">{c.value ?? <span className="text-[var(--border)]">—</span>}</div>
          </div>
        ))}
      </div>
    </div>
  )
}
