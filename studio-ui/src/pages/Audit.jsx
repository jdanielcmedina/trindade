import { useEffect, useState } from 'react'
import { api } from '../lib/api'

export default function Audit() {
  const [stats, setStats] = useState(null)
  const [entries, setEntries] = useState([])

  useEffect(() => { api.nis2Stats().then(setStats); load() }, [])
  const load = async () => { const r = await api.audit(); setEntries(r.entries || []) }

  return (
    <div>
      <div className="mb-8"><h1 className="text-lg font-semibold tracking-tight">Auditoria</h1></div>

      {stats && (
        <div className="grid grid-cols-4 gap-4 mb-6">
          {[
            { label: 'Eventos', value: stats.audit_count, color: 'var(--accent)' },
            { label: 'Lockouts', value: stats.lockouts, color: 'var(--amber)' },
            { label: 'Backups', value: stats.backups, color: 'var(--blue)' },
            { label: 'Alertas', value: stats.alerts, color: 'var(--red)' },
          ].map(c => (
            <div key={c.label} className="bg-[var(--surface)] border border-[var(--border)] rounded-xl p-4">
              <div className="text-[11px] uppercase tracking-wider text-[var(--text3)] font-medium mb-1">{c.label}</div>
              <div className="text-xl font-bold tracking-tight" style={{ color: c.color }}>{c.value}</div>
            </div>
          ))}
        </div>
      )}

      <div className="bg-[var(--surface)] border border-[var(--border)] rounded-xl">
        <div className="flex items-center justify-between px-5 py-3 border-b border-[var(--border)]">
          <h3 className="text-sm font-semibold">Registo de Auditoria</h3>
          <button onClick={load} className="text-xs text-[var(--accent)] hover:underline">Refresh</button>
        </div>
        <div className="max-h-[60vh] overflow-y-auto">
          {entries.map((e, i) => (
            <div key={i} className="flex items-center gap-4 px-5 py-2.5 border-b border-[var(--border)] hover:bg-[var(--surface2)] transition-colors text-xs">
              <span className="text-[var(--text3)] font-mono whitespace-nowrap w-[140px]">{(e.ts || '').replace('T', ' ').substring(0, 19)}</span>
              <span className="text-[var(--accent)] font-medium whitespace-nowrap min-w-[120px]">{e.action}</span>
              <span className="text-[var(--text3)] truncate">{e.ip} {e.user || ''}</span>
            </div>
          ))}
          {entries.length === 0 && <div className="px-5 py-8 text-center text-[var(--text3)] text-xs">Sem eventos de auditoria.</div>}
        </div>
      </div>
    </div>
  )
}
