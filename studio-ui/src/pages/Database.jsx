import { useEffect, useState } from 'react'
import { api } from '../lib/api'

export default function Database() {
  const [tables, setTables] = useState([])
  const [sql, setSql] = useState('')
  const [result, setResult] = useState(null)
  const [browse, setBrowse] = useState(null)

  useEffect(() => { api.dbTables().then(setTables).catch(() => {}) }, [])

  const run = async () => {
    const r = await api.dbQuery(sql)
    setResult(r)
  }

  const open = async (t) => {
    const r = await api.dbTable(t)
    setBrowse({ table: t, rows: r })
  }

  return (
    <div>
      <h2 className="text-base font-semibold mb-4">Base de Dados</h2>

      <div className="flex flex-wrap gap-2 mb-4">
        {tables.map(t => (
          <button key={t} onClick={() => open(t)} className="px-2.5 py-1 text-xs bg-[#161b22] border border-[#30363d] rounded-md hover:bg-[#21262d]">
            {t}
          </button>
        ))}
      </div>

      <div className="bg-[#161b22] border border-[#30363d] rounded-lg p-4 mb-4">
        <h3 className="text-sm font-semibold mb-2">Consola SQL</h3>
        <textarea value={sql} onChange={e => setSql(e.target.value)} rows={3} placeholder="SELECT * FROM users LIMIT 10" className="w-full bg-[#0d1117] border border-[#30363d] rounded px-3 py-2 text-xs font-mono mb-2" />
        <button onClick={run} className="px-3 py-1 text-xs bg-[#3b82f6] hover:bg-[#2563eb] rounded-md">Executar</button>
      </div>

      {result && (
        <div className="bg-[#161b22] border border-[#30363d] rounded-lg overflow-hidden">
          {result.error ? (
            <p className="p-3 text-xs text-[#da3633]">{result.error}</p>
          ) : (
            <div className="overflow-x-auto max-h-80">
              <table className="w-full text-xs">
                {result.rows?.[0] && (
                  <thead><tr className="bg-[#0d1117]">{Object.keys(result.rows[0]).map(k => <th key={k} className="text-left px-3 py-1.5 text-[#8b949e] font-medium">{k}</th>)}</tr></thead>
                )}
                <tbody>{result.rows?.map((r, i) => <tr key={i} className="border-t border-[#30363d]">{Object.values(r).map((v, j) => <td key={j} className="px-3 py-1">{v !== null ? String(v) : <span className="text-[#8b949e] italic">NULL</span>}</td>)}</tr>)}</tbody>
              </table>
            </div>
          )}
        </div>
      )}

      {browse && (
        <div className="mt-4 bg-[#161b22] border border-[#30363d] rounded-lg p-4">
          <h3 className="text-sm font-semibold mb-2">{browse.table} ({browse.rows.length})</h3>
          <div className="overflow-x-auto max-h-60">
            <table className="w-full text-xs">
              {browse.rows?.[0] && (
                <thead><tr className="bg-[#0d1117]">{Object.keys(browse.rows[0]).map(k => <th key={k} className="text-left px-3 py-1.5 text-[#8b949e] font-medium">{k}</th>)}</tr></thead>
              )}
              <tbody>{browse.rows?.map((r, i) => <tr key={i} className="border-t border-[#30363d]">{Object.values(r).map((v, j) => <td key={j} className="px-3 py-1">{v !== null ? String(v) : <span className="text-[#8b949e] italic">NULL</span>}</td>)}</tr>)}</tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  )
}
