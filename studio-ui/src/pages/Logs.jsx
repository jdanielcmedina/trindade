import { useEffect, useState } from 'react'
import { api } from '../lib/api'

export default function Logs() {
  const [logs, setLogs] = useState('')
  useEffect(() => { api.logs().then(setLogs) }, [])

  return (
    <div>
      <div className="flex items-center justify-between mb-8">
        <h1 className="text-lg font-semibold tracking-tight">Logs</h1>
        <button onClick={() => api.logs().then(setLogs)} className="text-xs text-[var(--accent)] hover:underline">Refresh</button>
      </div>
      <div className="bg-[var(--surface)] border border-[var(--border)] rounded-xl p-5">
        <pre className="text-xs font-mono text-[var(--text2)] whitespace-pre-wrap leading-relaxed max-h-[70vh] overflow-y-auto">{logs || 'No log entries.'}</pre>
      </div>
    </div>
  )
}
