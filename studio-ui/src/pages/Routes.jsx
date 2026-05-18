import { useEffect, useState } from 'react'
import { api } from '../lib/api'

const methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH']
const badgeStyles = {
  GET: 'text-[var(--green)] bg-[var(--green-bg)]',
  POST: 'text-[var(--blue)] bg-[var(--blue-bg)]',
  PUT: 'text-[var(--amber)] bg-[var(--amber-bg)]',
  DELETE: 'text-[var(--red)] bg-[var(--red-bg)]',
  PATCH: 'text-[var(--sky)] bg-[#0ea5e910]',
}

export default function Routes() {
  const [routes, setRoutes] = useState({})
  const [showAdd, setShowAdd] = useState(false)
  const [method, setMethod] = useState('GET')
  const [path, setPath] = useState('')
  const [code, setCode] = useState('')
  const [valid, setValid] = useState(null)

  useEffect(() => { api.routes().then(setRoutes) }, [])

  const save = async () => {
    await api.routeSave({ method, path, code })
    setShowAdd(false); setPath(''); setCode(''); setValid(null)
    api.routes().then(setRoutes)
  }

  const del = async (m, p) => {
    await api.routeDelete(m, p)
    api.routes().then(setRoutes)
  }

  const validate = async () => {
    const r = await api.routeValidate({ method, code })
    setValid(r)
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-lg font-semibold tracking-tight">Rotas</h1>
          <p className="text-sm text-[var(--text3)] mt-1">{Object.values(routes).reduce((a, r) => a + Object.keys(r).length, 0)} endpoints registados</p>
        </div>
        <button
          onClick={() => setShowAdd(!showAdd)}
          className="inline-flex items-center gap-2 px-4 py-2 bg-white text-black text-[13px] font-semibold rounded-lg hover:bg-gray-200 transition-colors"
        >
          <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M12 4.5v15m7.5-7.5h-15" /></svg>
          Nova Rota
        </button>
      </div>

      {showAdd && (
        <div className="mb-6 bg-[var(--surface)] border border-[var(--border)] rounded-xl p-5 animate-in">
          <h3 className="text-sm font-semibold mb-4">Nova Rota</h3>
          <div className="flex gap-3 mb-4">
            <select value={method} onChange={e => setMethod(e.target.value)}
              className="w-28 bg-[var(--bg)] border border-[var(--border)] rounded-lg px-3 py-2 text-xs font-mono font-medium outline-none focus:border-[var(--accent)]">
              {methods.map(m => <option key={m}>{m}</option>)}
            </select>
            <input value={path} onChange={e => setPath(e.target.value)} placeholder="/users/:id"
              className="flex-1 bg-[var(--bg)] border border-[var(--border)] rounded-lg px-3 py-2 text-xs font-mono outline-none focus:border-[var(--accent)] placeholder:text-[var(--text3)]" />
          </div>
          <textarea value={code} onChange={e => setCode(e.target.value)} rows={6}
            placeholder="$data = $app->body();&#10;$id = $app->param('id');&#10;return $app->success($data);"
            className="w-full bg-[var(--bg)] border border-[var(--border)] rounded-lg px-4 py-3 text-xs font-mono leading-relaxed outline-none focus:border-[var(--accent)] placeholder:text-[var(--text3)] resize-y mb-4" />

          {valid && (
            <div className={`mb-4 p-3 rounded-lg text-xs ${valid.ready ? 'bg-[var(--green-bg)] text-[var(--green)] border border-[var(--green)]/20' : 'bg-[var(--red-bg)] text-[var(--red)] border border-[var(--red)]/20'}`}>
              {valid.ready ? 'Pronto para producao' : valid.warnings.map((w, i) => <div key={i} className="flex items-start gap-2">{i > 0 && <br />}{w}</div>)}
            </div>
          )}

          <div className="flex gap-2">
            <button onClick={save} className="px-4 py-2 bg-white text-black text-[13px] font-semibold rounded-lg hover:bg-gray-200 transition-colors">Guardar</button>
            <button onClick={validate} className="px-4 py-2 bg-[var(--surface2)] border border-[var(--border)] text-[13px] rounded-lg hover:bg-[var(--bg)] transition-colors">Validar</button>
            <button onClick={() => { setShowAdd(false); setValid(null) }} className="px-4 py-2 text-[13px] text-[var(--text3)] hover:text-[var(--text)] transition-colors">Cancelar</button>
          </div>
        </div>
      )}

      <div className="space-y-px">
        {Object.entries(routes).map(([m, rs]) =>
          Object.entries(rs).map(([p]) => (
            <div key={m + p} className="group flex items-center gap-3 px-4 py-2.5 bg-[var(--surface)] border border-[var(--border)] first:rounded-t-lg last:rounded-b-lg hover:bg-[var(--surface2)] transition-colors">
              <span className={`px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wide ${badgeStyles[m] || ''}`}>{m}</span>
              <code className="flex-1 text-[13px] text-[var(--text)] font-mono">{p}</code>
              <button onClick={() => del(m, p)} className="text-[11px] text-[var(--text3)] hover:text-[var(--red)] opacity-0 group-hover:opacity-100 transition-all">Remove</button>
            </div>
          ))
        )}
      </div>
    </div>
  )
}
