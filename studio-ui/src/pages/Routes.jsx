import { useEffect, useState } from 'react'
import { api } from '../lib/api'

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
    setShowAdd(false)
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

  const badgeColor = (m) => {
    const c = { GET: 'bg-[#238636]/20 text-[#238636]', POST: 'bg-[#3b82f6]/20 text-[#3b82f6]', PUT: 'bg-[#d29922]/20 text-[#d29922]', DELETE: 'bg-[#da3633]/20 text-[#da3633]', PATCH: 'bg-[#58a6ff]/20 text-[#58a6ff]' }
    return c[m] || 'bg-[#30363d] text-[#8b949e]'
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-4">
        <h2 className="text-base font-semibold">Rotas</h2>
        <button onClick={() => setShowAdd(!showAdd)} className="px-3 py-1 text-xs bg-[#3b82f6] hover:bg-[#2563eb] rounded-md transition-colors">
          Nova Rota
        </button>
      </div>

      {showAdd && (
        <div className="bg-[#161b22] border border-[#30363d] rounded-lg p-4 mb-4">
          <h3 className="text-sm font-semibold mb-3">Nova Rota</h3>
          <div className="grid grid-cols-4 gap-3 mb-3">
            <select value={method} onChange={e => setMethod(e.target.value)} className="bg-[#0d1117] border border-[#30363d] rounded px-2 py-1 text-xs">
              <option>GET</option><option>POST</option><option>PUT</option><option>DELETE</option><option>PATCH</option>
            </select>
            <input value={path} onChange={e => setPath(e.target.value)} placeholder="/users/:id" className="col-span-3 bg-[#0d1117] border border-[#30363d] rounded px-2 py-1 text-xs" />
          </div>
          <textarea value={code} onChange={e => setCode(e.target.value)} rows={6} placeholder="$data = $app->body(); return $app->success($data);" className="w-full bg-[#0d1117] border border-[#30363d] rounded px-3 py-2 text-xs font-mono mb-3" />
          <div className="flex gap-2 items-center">
            <button onClick={save} className="px-3 py-1 text-xs bg-[#238636] hover:bg-[#2ea043] rounded-md">Guardar</button>
            <button onClick={validate} className="px-3 py-1 text-xs bg-[#161b22] border border-[#30363d] hover:bg-[#21262d] rounded-md">Validar</button>
            <button onClick={() => setShowAdd(false)} className="px-3 py-1 text-xs text-[#8b949e] hover:text-white">Cancelar</button>
          </div>
          {valid && (
            <div className={`mt-3 p-2 rounded text-xs ${valid.ready ? 'bg-[#238636]/10 text-[#238636]' : 'bg-[#da3633]/10 text-[#da3633]'}`}>
              {valid.ready ? 'Pronto para producao.' : valid.warnings.map((w, i) => <div key={i}>{w}</div>)}
            </div>
          )}
        </div>
      )}

      <div className="space-y-0.5">
        {Object.entries(routes).map(([m, rs]) =>
          Object.entries(rs).map(([p]) => (
            <div key={m + p} className="flex items-center gap-2 py-1.5 px-3 bg-[#161b22] border border-[#30363d] rounded-md text-xs">
              <span className={`px-1.5 py-0.5 rounded text-[10px] font-bold ${badgeColor(m)}`}>{m}</span>
              <code className="flex-1 text-xs">{p}</code>
              <button onClick={() => del(m, p)} className="text-[10px] text-[#da3633] hover:underline">Remover</button>
            </div>
          ))
        )}
      </div>
    </div>
  )
}
