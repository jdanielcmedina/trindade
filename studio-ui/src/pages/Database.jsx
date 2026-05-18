import { useEffect, useState } from 'react'
import { api } from '../lib/api'

export default function Database() {
  const [tables, setTables] = useState([])
  const [sql, setSql] = useState('')
  const [result, setResult] = useState(null)
  const [browse, setBrowse] = useState(null)

  useEffect(() => { api.dbTables().then(setTables).catch(() => {}) }, [])

  const run = async () => { const r = await api.dbQuery(sql); setResult(r) }
  const open = async (t) => { const r = await api.dbTable(t); setBrowse({ table: t, rows: r }) }

  return (
    <div>
      <div className="mb-8">
        <h1 className="text-lg font-semibold tracking-tight">Base de Dados</h1>
        <p className="text-sm text-[var(--text3)] mt-1">{tables.length} tables</p>
      </div>

      <div className="flex flex-wrap gap-1.5 mb-6">
        {tables.map(t => (
          <button key={t} onClick={() => open(t)} className="px-3 py-1.5 text-[12px] font-mono rounded-lg bg-[var(--surface)] border border-[var(--border)] hover:border-[var(--border-hover)] hover:bg-[var(--surface2)] transition-all text-[var(--text2)] hover:text-[var(--text)]">
            {t}
          </button>
        ))}
      </div>

      <div className="bg-[var(--surface)] border border-[var(--border)] rounded-xl p-5 mb-6">
        <h3 className="text-sm font-semibold mb-3">SQL Console</h3>
        <textarea value={sql} onChange={e => setSql(e.target.value)} rows={3} placeholder="SELECT * FROM users LIMIT 10"
          className="w-full bg-[var(--bg)] border border-[var(--border)] rounded-lg px-4 py-3 text-xs font-mono outline-none focus:border-[var(--accent)] resize-y mb-3 placeholder:text-[var(--text3)]" />
        <button onClick={run} className="px-4 py-2 bg-white text-black text-[13px] font-semibold rounded-lg hover:bg-gray-200 transition-colors">Executar</button>
      </div>

      {(result || browse) && (
        <div className="bg-[var(--surface)] border border-[var(--border)] rounded-xl overflow-hidden">
          <div className="px-5 py-3 border-b border-[var(--border)] flex items-center justify-between">
            <span className="text-xs font-medium text-[var(--text2)]">
              {browse ? `${browse.table} — ${browse.rows.length} rows` : 'Query result'}
            </span>
          </div>
          <div className="overflow-x-auto max-h-96">
            <table className="w-full text-xs">
              {((result?.rows?.[0]) || (browse?.rows?.[0])) && (
                <thead>
                  <tr className="bg-[var(--bg)]">
                    {Object.keys((result?.rows?.[0]) || (browse?.rows?.[0])).map(k => (
                      <th key={k} className="text-left px-4 py-2 text-[var(--text3)] font-medium font-mono text-[11px] uppercase tracking-wider">{k}</th>
                    ))}
                  </tr>
                </thead>
              )}
              <tbody>
                {(result?.rows || browse?.rows || []).map((r, i) => (
                  <tr key={i} className="border-t border-[var(--border)] hover:bg-[var(--surface2)] transition-colors">
                    {Object.values(r).map((v, j) => (
                      <td key={j} className="px-4 py-2 font-mono text-[var(--text2)]">{v !== null ? String(v) : <span className="text-[var(--text3)] italic">NULL</span>}</td>
                    ))}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  )
}
