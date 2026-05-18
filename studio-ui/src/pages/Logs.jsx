import { useEffect, useState } from 'react'
import { api } from '../lib/api'

export default function Logs() {
  const [logs, setLogs] = useState('')
  useEffect(() => { api.logs().then(setLogs) }, [])
  return (
    <div>
      <h2 className="text-base font-semibold mb-4">Logs da Aplicacao</h2>
      <pre className="bg-[#161b22] border border-[#30363d] rounded-lg p-4 text-xs font-mono text-[#8b949e] max-h-[70vh] overflow-y-auto whitespace-pre-wrap">{logs}</pre>
    </div>
  )
}
