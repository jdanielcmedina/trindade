import { useState } from 'react'
import { api } from '../lib/api'

export default function Console() {
  const [method, setMethod] = useState('GET')
  const [url, setUrl] = useState('')
  const [headers, setHeaders] = useState('{"Content-Type":"application/json"}')
  const [body, setBody] = useState('')
  const [result, setResult] = useState(null)

  const send = async () => {
    let h = {}
    try { h = JSON.parse(headers) } catch {}
    const r = await api.proxy(method, url, h, body)
    setResult(r)
  }

  return (
    <div>
      <h2 className="text-base font-semibold mb-4">Consola API</h2>
      <div className="bg-[#161b22] border border-[#30363d] rounded-lg p-4">
        <div className="flex gap-2 mb-3">
          <select value={method} onChange={e => setMethod(e.target.value)} className="bg-[#0d1117] border border-[#30363d] rounded px-2 py-1 text-xs">
            <option>GET</option><option>POST</option><option>PUT</option><option>DELETE</option><option>PATCH</option>
          </select>
          <input value={url} onChange={e => setUrl(e.target.value)} placeholder="/api/v1/endpoint" className="flex-1 bg-[#0d1117] border border-[#30363d] rounded px-2 py-1 text-xs" />
        </div>
        <textarea value={headers} onChange={e => setHeaders(e.target.value)} rows={2} className="w-full bg-[#0d1117] border border-[#30363d] rounded px-3 py-2 text-xs font-mono mb-2" />
        <textarea value={body} onChange={e => setBody(e.target.value)} rows={4} placeholder='{"key":"value"}' className="w-full bg-[#0d1117] border border-[#30363d] rounded px-3 py-2 text-xs font-mono mb-2" />
        <button onClick={send} className="px-3 py-1 text-xs bg-[#3b82f6] hover:bg-[#2563eb] rounded-md">Enviar</button>
      </div>
      {result && (
        <div className="mt-4 bg-[#161b22] border border-[#30363d] rounded-lg p-4">
          <h3 className="text-sm font-semibold mb-2">Resposta</h3>
          <pre className="text-xs font-mono text-[#8b949e] whitespace-pre-wrap">{JSON.stringify(result, null, 2)}</pre>
        </div>
      )}
    </div>
  )
}
