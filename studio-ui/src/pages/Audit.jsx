import { useEffect, useState } from 'react'
import { api } from '../lib/api'

export default function Audit() {
  const [stats, setStats] = useState(null)
  const [entries, setEntries] = useState([])

  useEffect(() => {
    api.nis2Stats().then(setStats)
    loadAudit()
  }, [])

  const loadAudit = async () => {
    const r = await api.audit()
    setEntries(r.entries || [])
  }

  return (
    <div>
      <h2 className="text-base font-semibold mb-4">Auditoria</h2>

      {stats && (
        <div className="grid grid-cols-4 gap-3 mb-4">
          {[
            { label: 'Eventos', value: stats.audit_count },
            { label: 'Lockouts', value: stats.lockouts },
            { label: 'Backups', value: stats.backups },
            { label: 'Alertas', value: stats.alerts },
          ].map(c => (
            <div key={c.label} className="bg-[#161b22] border border-[#30363d] rounded-lg p-3">
              <div className="text-[11px] text-[#8b949e] uppercase">{c.label}</div>
              <div className="text-lg font-semibold">{c.value}</div>
            </div>
          ))}
        </div>
      )}

      <div className="bg-[#161b22] border border-[#30363d] rounded-lg p-4">
        <div className="flex items-center justify-between mb-2">
          <h3 className="text-sm font-semibold">Registo de Auditoria</h3>
          <button onClick={loadAudit} className="text-xs text-[#3b82f6] hover:underline">Refresh</button>
        </div>
        <div className="max-h-96 overflow-y-auto space-y-0.5">
          {entries.map((e, i) => (
            <div key={i} className="flex gap-3 text-xs py-1 border-b border-[#30363d]">
              <span className="text-[#8b949e] whitespace-nowrap">{(e.ts || '').replace('T', ' ').substring(0, 19)}</span>
              <span className="text-[#3b82f6] font-medium whitespace-nowrap">{e.action}</span>
              <span className="text-[#8b949e] truncate">{e.ip} {e.user || ''}</span>
            </div>
          ))}
          {entries.length === 0 && <p className="text-xs text-[#8b949e]">Sem eventos de auditoria.</p>}
        </div>
      </div>
    </div>
  )
}
