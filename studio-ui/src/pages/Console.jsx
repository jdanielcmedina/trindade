import { useState } from 'react'
import { api } from '../lib/api'

export default function Console() {
  const [method, setMethod] = useState('GET')
  const [url, setUrl] = useState('')
  const [headers, setHeaders] = useState('{\n  "Content-Type": "application/json"\n}')
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
      <div className="mb-8"><h1 className="text-lg font-semibold tracking-tight">Consola API</h1></div>
      <div className="bg-[var(--surface)] border border-[var(--border)] rounded-xl p-5">
        <div className="flex gap-3 mb-4">
          <select value={method} onChange={e => setMethod(e.target.value)} className="w-24 bg-[var(--bg)] border border-[var(--border)] rounded-lg px-3 py-2 text-xs font-mono font-medium outline-none focus:border-[var(--accent)]">
            {['GET', 'POST', 'PUT', 'DELETE', 'PATCH'].map(m => <option key={m}>{m}</option>)}
          </select>
          <input value={url} onChange={e => setUrl(e.target.value)} placeholder="/api/v1/endpoint" className="flex-1 bg-[var(--bg)] border border-[var(--border)] rounded-lg px-3 py-2 text-xs font-mono outline-none focus:border-[var(--accent)] placeholder:text-[var(--text3)]" />
        </div>
        <textarea value={headers} onChange={e => setHeaders(e.target.value)} rows={3} className="w-full bg-[var(--bg)] border border-[var(--border)] rounded-lg px-4 py-3 text-xs font-mono outline-none focus:border-[var(--accent)] resize-y mb-3" />
        <textarea value={body} onChange={e => setBody(e.target.value)} rows={5} placeholder='{"key": "value"}'
          className="w-full bg-[var(--bg)] border border-[var(--border)] rounded-lg px-4 py-3 text-xs font-mono outline-none focus:border-[var(--accent)] resize-y mb-4 placeholder:text-[var(--text3)]" />
        <button onClick={send} className="px-4 py-2 bg-white text-black text-[13px] font-semibold rounded-lg hover:bg-gray-200 transition-colors">Enviar</button>
      </div>
      {result && (
        <div className="mt-4 bg-[var(--surface)] border border-[var(--border)] rounded-xl p-5">
          <h3 className="text-sm font-semibold mb-3">Resposta</h3>
          <pre className="text-xs font-mono text-[var(--text2)] whitespace-pre-wrap leading-relaxed">{JSON.stringify(result, null, 2)}</pre>
        </div>
      )}
    </div>
  )
}
