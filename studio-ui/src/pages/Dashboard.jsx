import { useEffect, useState } from 'react'
import { api } from '../lib/api'

export default function Dashboard() {
  const [stats, setStats] = useState(null)
  useEffect(() => { api.stats().then(setStats) }, [])

  const cards = [
    { label: 'PHP', value: stats?.php },
    { label: 'Rotas', value: stats?.routes },
    { label: 'Tabelas', value: stats?.tables },
    { label: 'Storage', value: stats?.storage },
  ]

  return (
    <div>
      <h2 className="text-base font-semibold mb-4">Dashboard</h2>
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
        {cards.map(c => (
          <div key={c.label} className="bg-[#161b22] border border-[#30363d] rounded-lg p-4">
            <div className="text-[11px] text-[#8b949e] uppercase tracking-wide">{c.label}</div>
            <div className="text-xl font-semibold mt-1">{c.value ?? '-'}</div>
          </div>
        ))}
      </div>
    </div>
  )
}
